<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShopBillController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->shopUser($request);

        if (! $user) {
            return $this->shopUserForbiddenResponse();
        }

        $perPage = min((int) $request->integer('per_page', 15), 100);

        $bills = Bill::query()
            ->with(['customer'])
            ->where('company_id', $user->company_id)
            ->when($this->isBranchEmployee($user), fn ($query) => $query->where('branch_id', $user->branch_id))
            ->latest()
            ->paginate($perPage)
            ->through(fn (Bill $bill): array => $this->formatBillSummary($bill));

        return response()->json($bills);
    }

    public function show(Request $request, Bill $bill)
    {
        $user = $this->shopUser($request);

        if (! $user) {
            return $this->shopUserForbiddenResponse();
        }

        if (! $this->userCanAccessBill($bill, $user)) {
            return $this->billForbiddenResponse();
        }

        $bill->load(['company', 'branch', 'customer', 'billItems.product']);

        return response()->json([
            'bill' => $this->formatBillDetail($bill),
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->shopUser($request);

        if (! $user || ! $user->company_id) {
            return $this->shopUserForbiddenResponse();
        }

        if ($this->isBranchEmployee($user) && ! $user->branch_id) {
            return $this->shopUserForbiddenResponse();
        }

        $user->loadMissing(['company.subscriptionPackage']);

        if (! $user->company?->canCreateBills()) {
            return response()->json([
                'message' => 'Company subscription is inactive or expired.',
            ], 403);
        }

        $validated = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where('company_id', $user->company_id),
            ],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['nullable', Rule::in(['unpaid', 'partial', 'paid'])],
            'payment_method' => ['nullable', Rule::in(['cash', 'card', 'bank_transfer', 'mixed'])],
            'status' => ['nullable', Rule::in(['in_process', 'ready', 'delivered'])],
            'items' => ['nullable', 'array'],
            'items.*.product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where('company_id', $user->company_id),
            ],
            'items.*.item_name' => ['nullable', 'string', 'max:255'],
            'items.*.item_type' => ['nullable', Rule::in(['service', 'product'])],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($this->isBranchEmployee($user)) {
            if (! empty($validated['branch_id']) && (int) $validated['branch_id'] !== (int) $user->branch_id) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Branch employees can create bills only for their assigned branch.',
                ]);
            }

            $validated['branch_id'] = $user->branch_id;
        }

        $customer = $this->resolveCustomer($validated);
        $customerPhone = $this->normalizeCustomerPhoneForBill($validated['customer_phone']);

        $bill = DB::transaction(function () use ($customer, $customerPhone, $user, $validated): Bill {
            $bill = Bill::create([
                'company_id' => $user->company_id,
                'branch_id' => $validated['branch_id'] ?? null,
                'customer_id' => $customer->id,
                'bill_number' => $this->generateBillNumber(),
                'customer_phone' => $customerPhone,
                'paid_amount' => $validated['paid_amount'] ?? 0,
                'payment_status' => $validated['payment_status'] ?? 'unpaid',
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'status' => $validated['status'] ?? 'in_process',
                'created_by' => $user->id,
            ]);

            if (array_key_exists('items', $validated)) {
                $this->replaceBillItems($bill, $validated['items'] ?? [], $user);
            }

            $this->syncBillAmounts($bill, $validated['payment_status'] ?? null);

            return $bill;
        });

        $bill->load(['company', 'branch', 'customer', 'billItems.product']);
        $this->createBillNotification(
            $bill,
            'New bill created',
            'A new bill has been created by ' . ($bill->company?->name ?? 'the shop'),
        );

        return response()->json([
            'bill' => $this->formatBillDetail($bill),
        ], 201);
    }

    public function update(Request $request, Bill $bill)
    {
        $user = $this->shopUser($request);

        if (! $user) {
            return $this->shopUserForbiddenResponse();
        }

        if (! $this->userCanAccessBill($bill, $user)) {
            return $this->billForbiddenResponse();
        }

        $validated = $request->validate([
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['required', Rule::in(['unpaid', 'partial', 'paid'])],
            'payment_method' => ['nullable', Rule::in(['cash', 'card', 'bank_transfer', 'mixed'])],
            'status' => ['required', Rule::in(['in_process', 'ready', 'delivered'])],
            'items' => ['nullable', 'array'],
            'items.*.product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where('company_id', $user->company_id),
            ],
            'items.*.item_name' => ['nullable', 'string', 'max:255'],
            'items.*.item_type' => ['nullable', Rule::in(['service', 'product'])],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $oldStatus = $bill->status;

        DB::transaction(function () use ($bill, $user, $validated): void {
            $bill->update([
                'paid_amount' => $validated['paid_amount'] ?? $bill->paid_amount,
                'payment_status' => $validated['payment_status'],
                'payment_method' => $validated['payment_method'] ?? $bill->payment_method,
                'status' => $validated['status'],
            ]);

            if (array_key_exists('items', $validated)) {
                $this->replaceBillItems($bill, $validated['items'] ?? [], $user);
            }

            $this->syncBillAmounts($bill, $validated['payment_status']);
        });

        $bill->load(['company', 'branch', 'customer', 'billItems.product']);
        $this->createStatusNotificationIfNeeded($bill, $oldStatus);

        return response()->json([
            'bill' => $this->formatBillDetail($bill),
        ]);
    }

    public function destroy(Request $request, Bill $bill)
    {
        $user = $this->shopUser($request);

        if (! $user) {
            return $this->shopUserForbiddenResponse();
        }

        if (! $this->userCanAccessBill($bill, $user)) {
            return $this->billForbiddenResponse();
        }

        $bill->delete();

        return response()->json([
            'message' => 'Bill deleted.',
        ]);
    }

    public function updateStatus(Request $request, Bill $bill)
    {
        $user = $this->shopUser($request);

        if (! $user) {
            return $this->shopUserForbiddenResponse();
        }

        if (! $this->userCanAccessBill($bill, $user)) {
            return $this->billForbiddenResponse();
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['in_process', 'ready', 'delivered'])],
        ]);

        $oldStatus = $bill->status;

        $bill->update([
            'status' => $validated['status'],
        ]);

        $bill->load(['company', 'branch', 'customer', 'billItems.product']);
        $this->createStatusNotificationIfNeeded($bill, $oldStatus);

        return response()->json([
            'bill' => $this->formatBillDetail($bill),
        ]);
    }

    private function shopUser(Request $request): ?User
    {
        $user = $request->user();

        return $user instanceof User ? $user : null;
    }

    private function billBelongsToUserCompany(Bill $bill, User $user): bool
    {
        return (int) $bill->company_id === (int) $user->company_id;
    }

    private function userCanAccessBill(Bill $bill, User $user): bool
    {
        if (! $this->billBelongsToUserCompany($bill, $user)) {
            return false;
        }

        if ($this->isBranchEmployee($user)) {
            return (int) $bill->branch_id === (int) $user->branch_id;
        }

        return true;
    }

    private function isBranchEmployee(User $user): bool
    {
        return $user->role === 'branch_employee';
    }

    private function resolveCustomer(array $validated): Customer
    {
        if (! empty($validated['customer_id'])) {
            return Customer::findOrFail($validated['customer_id']);
        }

        [$countryCode, $phone] = $this->splitPhoneForCustomer($validated['customer_phone']);

        return Customer::firstOrCreate(
            [
                'country_code' => $countryCode,
                'phone' => $phone,
            ],
            [
                'active' => true,
            ],
        );
    }

    private function splitPhoneForCustomer(string $customerPhone): array
    {
        $phoneDigits = $this->normalizePhone($customerPhone);

        if ($phoneDigits === '') {
            throw ValidationException::withMessages([
                'customer_phone' => 'The customer phone must contain digits.',
            ]);
        }

        $knownCountryCodes = ['968', '971', '966', '974', '973', '965'];

        foreach ($knownCountryCodes as $countryCode) {
            if (str_starts_with($phoneDigits, $countryCode)) {
                $localPhone = substr($phoneDigits, strlen($countryCode));

                if ($localPhone === '') {
                    throw ValidationException::withMessages([
                        'customer_phone' => 'The customer phone must include a local number.',
                    ]);
                }

                return [
                    '+' . $countryCode,
                    $localPhone,
                ];
            }
        }

        return [
            '+968',
            $phoneDigits,
        ];
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone);
    }

    private function normalizeCustomerPhoneForBill(string $customerPhone): string
    {
        [$countryCode, $phone] = $this->splitPhoneForCustomer($customerPhone);

        return $countryCode . $phone;
    }

    private function replaceBillItems(Bill $bill, array $items, User $user): void
    {
        $bill->billItems()->delete();

        foreach ($items as $item) {
            $this->createBillItem($bill, $item, $user);
        }
    }

    private function createBillItem(Bill $bill, array $item, User $user): BillItem
    {
        $product = null;

        if (! empty($item['product_id'])) {
            $product = Product::query()
                ->where('company_id', $user->company_id)
                ->findOrFail($item['product_id']);
        }

        $itemName = $item['item_name'] ?? $product?->name;

        if (! $itemName) {
            throw ValidationException::withMessages([
                'items' => 'Each bill item must include item_name when product_id is not provided.',
            ]);
        }

        $quantity = round((float) $item['quantity'], 3);
        $unitPrice = round((float) ($item['unit_price'] ?? $product?->price ?? 0), 3);

        return $bill->billItems()->create([
            'product_id' => $product?->id,
            'item_name' => $itemName,
            'item_type' => $item['item_type'] ?? $product?->type ?? 'service',
            'description' => $item['description'] ?? null,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total' => round($quantity * $unitPrice, 3),
        ]);
    }

    private function syncBillAmounts(Bill $bill, ?string $requestedPaymentStatus = null): void
    {
        $totalAmount = round((float) $bill->billItems()->sum('total'), 3);
        $paidAmount = min(round((float) $bill->paid_amount, 3), $totalAmount);
        $remainingAmount = round(max($totalAmount - $paidAmount, 0), 3);

        $bill->forceFill([
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'payment_status' => $requestedPaymentStatus ?? $this->paymentStatusForAmounts($paidAmount, $totalAmount),
        ])->save();
    }

    private function paymentStatusForAmounts(float $paidAmount, float $totalAmount): string
    {
        if ($totalAmount <= 0 || $paidAmount <= 0) {
            return 'unpaid';
        }

        if ($paidAmount >= $totalAmount) {
            return 'paid';
        }

        return 'partial';
    }

    private function generateBillNumber(): string
    {
        do {
            $billNumber = 'BILL-' . now()->format('Ymd') . '-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Bill::where('bill_number', $billNumber)->exists());

        return $billNumber;
    }

    private function formatBillSummary(Bill $bill): array
    {
        return [
            'id' => $bill->id,
            'bill_number' => $bill->bill_number,
            'customer_name' => $bill->customer?->name,
            'customer_phone' => $bill->customer_phone,
            'total_amount' => $bill->total_amount,
            'payment_status' => $bill->payment_status,
            'payment_method' => $bill->payment_method,
            'status' => $bill->status,
            'created_at' => $bill->created_at,
        ];
    }

    private function formatBillDetail(Bill $bill): array
    {
        return [
            'id' => $bill->id,
            'company_id' => $bill->company_id,
            'company' => $bill->company,
            'branch_id' => $bill->branch_id,
            'branch' => $bill->branch,
            'customer_id' => $bill->customer_id,
            'customer' => $bill->customer,
            'bill_number' => $bill->bill_number,
            'customer_phone' => $bill->customer_phone,
            'total_amount' => $bill->total_amount,
            'paid_amount' => $bill->paid_amount,
            'remaining_amount' => $bill->remaining_amount,
            'payment_status' => $bill->payment_status,
            'payment_method' => $bill->payment_method,
            'status' => $bill->status,
            'created_by' => $bill->created_by,
            'created_at' => $bill->created_at,
            'updated_at' => $bill->updated_at,
            'bill_items' => $bill->billItems
                ->map(fn ($billItem): array => [
                    'id' => $billItem->id,
                    'product_id' => $billItem->product_id,
                    'product' => $billItem->product,
                    'item_name' => $billItem->item_name,
                    'item_type' => $billItem->item_type,
                    'description' => $billItem->description,
                    'quantity' => $billItem->quantity,
                    'unit_price' => $billItem->unit_price,
                    'total' => $billItem->total,
                    'created_at' => $billItem->created_at,
                    'updated_at' => $billItem->updated_at,
                ]),
        ];
    }

    private function createStatusNotificationIfNeeded(Bill $bill, string $oldStatus): void
    {
        if ($oldStatus === $bill->status) {
            return;
        }

        match ($bill->status) {
            'ready' => $this->createBillNotification(
                $bill,
                'Bill is ready',
                'Your bill ' . $bill->bill_number . ' is ready',
            ),
            'delivered' => $this->createBillNotification(
                $bill,
                'Bill delivered',
                'Your bill ' . $bill->bill_number . ' has been delivered',
            ),
            default => null,
        };
    }

    private function createBillNotification(Bill $bill, string $title, string $message): void
    {
        if (! $bill->customer_id) {
            return;
        }

        Notification::create([
            'customer_id' => $bill->customer_id,
            'bill_id' => $bill->id,
            'title' => $title,
            'message' => $message,
        ]);
    }

    private function shopUserForbiddenResponse()
    {
        return response()->json([
            'message' => 'Authenticated user is not allowed to access shop bills.',
        ], 403);
    }

    private function billForbiddenResponse()
    {
        return response()->json([
            'message' => 'This bill does not belong to the authenticated company.',
        ], 403);
    }
}
