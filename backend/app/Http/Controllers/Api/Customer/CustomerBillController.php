<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use Illuminate\Http\Request;

class CustomerBillController extends Controller
{
    public function index(Request $request)
    {
        $customer = $request->user();

        $bills = Bill::query()
            ->with(['company', 'branch'])
            ->where('customer_id', $customer->id)
            ->latest()
            ->get()
            ->map(fn (Bill $bill): array => $this->formatBill($bill));

        return response()->json([
            'bills' => $bills,
        ]);
    }

    public function show(Request $request, Bill $bill)
    {
        $customer = $request->user();

        if ((int) $bill->customer_id !== (int) $customer->id) {
            return response()->json([
                'message' => 'This bill does not belong to the authenticated customer.',
            ], 403);
        }

        $bill->load(['company', 'branch', 'billItems.product']);

        return response()->json([
            'bill' => [
                ...$this->formatBill($bill),
                'bill_items' => $bill->billItems
                    ->map(fn ($billItem): array => [
                        'id' => $billItem->id,
                        'product_id' => $billItem->product_id,
                        'product_name' => $billItem->product?->name,
                        'description' => $billItem->description,
                        'quantity' => $billItem->quantity,
                        'unit_price' => $billItem->unit_price,
                        'total' => $billItem->total,
                        'created_at' => $billItem->created_at,
                    ]),
            ],
        ]);
    }

    private function formatBill(Bill $bill): array
    {
        return [
            'id' => $bill->id,
            'bill_number' => $bill->bill_number,
            'company_name' => $bill->company?->name,
            'branch_name' => $bill->branch?->name,
            'customer_phone' => $bill->customer_phone,
            'total_amount' => $bill->total_amount,
            'paid_amount' => $bill->paid_amount,
            'remaining_amount' => $bill->remaining_amount,
            'payment_status' => $bill->payment_status,
            'status' => $bill->status,
            'created_at' => $bill->created_at,
        ];
    }
}
