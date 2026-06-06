<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
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

        if (! $this->billBelongsToUserCompany($bill, $user)) {
            return $this->billForbiddenResponse();
        }

        $bill->load(['company', 'branch', 'customer', 'billItems.product']);
        $this->createBillNotification(
            $bill,
            'New bill created',
            'A new bill has been created by ' . ($bill->company?->name ?? 'the shop'),
        );

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
            'payment_status' => ['nullable', Rule::in(['unpaid', 'partial', 'paid'])],
            'status' => ['nullable', Rule::in(['in_process', 'ready', 'delivered'])],
        ]);

        $customer = $this->resolveCustomer($validated);
        $customerPhone = $this->normalizeCustomerPhoneForBill($validated['customer_phone']);

        $bill = Bill::create([
            'company_id' => $user->company_id,
            'branch_id' => $validated['branch_id'] ?? null,
            'customer_id' => $customer->id,
            'bill_number' => $this->generateBillNumber(),
            'customer_phone' => $customerPhone,
            'payment_status' => $validated['payment_status'] ?? 'unpaid',
            'status' => $validated['status'] ?? 'in_process',
            'created_by' => $user->id,
        ]);

        $bill->load(['company', 'branch', 'customer', 'billItems.product']);

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

        if (! $this->billBelongsToUserCompany($bill, $user)) {
            return $this->billForbiddenResponse();
        }

        $validated = $request->validate([
            'payment_status' => ['required', Rule::in(['unpaid', 'partial', 'paid'])],
            'status' => ['required', Rule::in(['in_process', 'ready', 'delivered'])],
        ]);

        $oldStatus = $bill->status;

        $bill->update($validated);
        $bill->load(['company', 'branch', 'customer', 'billItems.product']);
        $this->createStatusNotificationIfNeeded($bill, $oldStatus);

        return response()->json([
            'bill' => $this->formatBillDetail($bill),
        ]);
    }

    public function updateStatus(Request $request, Bill $bill)
    {
        $user = $this->shopUser($request);

        if (! $user) {
            return $this->shopUserForbiddenResponse();
        }

        if (! $this->billBelongsToUserCompany($bill, $user)) {
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
            'status' => $bill->status,
            'created_by' => $bill->created_by,
            'created_at' => $bill->created_at,
            'updated_at' => $bill->updated_at,
            'bill_items' => $bill->billItems
                ->map(fn ($billItem): array => [
                    'id' => $billItem->id,
                    'product_id' => $billItem->product_id,
                    'product' => $billItem->product,
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
