<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class SellerController extends Controller
{
    private function seller()
    {
        return auth()->user()->seller;
    }

    public function dashboard()
    {
        $s = $this->seller();

        return response()->json([
            'stats' => [
                'total' => $s->totalOrders(),
                'delivered' => $s->deliveredOrders(),
                'pending' => $s->pendingOrders(),
                'failed' => $s->failedOrders(),
                'total_spent' => $s->totalSpent(),
                'this_month_spent' => $s->thisMonthSpent(),
            ],
            'recent_orders' => $s->orders()->with('driver.user')->latest()->take(5)->get()
                ->map(fn ($o) => [
                    'id' => $o->id,
                    'order_number' => $o->order_number,
                    'customer_name' => $o->customer_name,
                    'customer_phone' => $o->customer_phone,
                    'product_price' => $o->product_price,
                    'delivery_fee' => $o->delivery_fee,
                    'driver_name' => $o->driver?->user?->name,
                    'status' => $o->status,
                    'created_at' => $o->created_at,
                ]),
        ]);
    }

    public function orders(Request $request)
    {
        $query = $this->seller()->orders()->with('driver.user');
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $orders = $query->latest()->paginate(15);

        return response()->json([
            'data' => $orders->map(fn ($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'customer_name' => $o->customer_name,
                'customer_phone' => $o->customer_phone,
                'delivery_address' => $o->delivery_address,
                'product_price' => $o->product_price,
                'delivery_fee' => $o->delivery_fee,
                'driver_name' => $o->driver?->user?->name,
                'status' => $o->status,
                'created_at' => $o->created_at,
            ]),
            'last_page' => $orders->lastPage(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'min:3', 'max:100'],
            'customer_phone' => ['required', 'string', 'regex:/^07[\d\s]{9,11}$/'],
            'delivery_address' => ['required', 'string'],
            'product_description' => ['required', 'string', 'min:3'],
            'product_price' => ['nullable', 'numeric', 'min:0'],
            'delivery_fee' => ['required', 'numeric', 'min:0.01'],
            'special_instructions' => ['nullable', 'string'],
            'delivery_zone' => ['nullable', 'string', 'max:100'],
        ]);

        $data['customer_phone'] = preg_replace('/\s+/', '', $data['customer_phone']);

        $order = $this->seller()->orders()->create($data);

        return response()->json(['message' => 'Order created.', 'order_number' => $order->order_number], 201);
    }

    public function show(Order $order)
    {
        if ($order->seller_id !== $this->seller()->id) {
            abort(403);
        }
        $order->load('driver.user');

        return response()->json([
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'delivery_address' => $order->delivery_address,
            'product_description' => $order->product_description,
            'product_price' => $order->product_price,
            'delivery_fee' => $order->delivery_fee,
            'special_instructions' => $order->special_instructions,
            'delivery_zone' => $order->delivery_zone,
            'status' => $order->status,
            'failure_reason' => $order->failure_reason,
            'driver_name' => $order->driver?->user?->name,
            'created_at' => $order->created_at,
            'delivered_at' => $order->delivered_at,
        ]);
    }

    public function update(Request $request, Order $order)
    {
        if ($order->seller_id !== $this->seller()->id) {
            abort(403);
        }
        if (! $order->isEditable()) {
            return response()->json(['message' => 'Only pending orders can be edited.'], 422);
        }

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'min:3', 'max:100'],
            'customer_phone' => ['required', 'string', 'regex:/^07[\d\s]{9,11}$/'],
            'delivery_address' => ['required', 'string'],
            'product_description' => ['required', 'string', 'min:3'],
            'product_price' => ['nullable', 'numeric', 'min:0'],
            'delivery_fee' => ['required', 'numeric', 'min:0.01'],
            'special_instructions' => ['nullable', 'string'],
            'delivery_zone' => ['nullable', 'string'],
        ]);

        $data['customer_phone'] = preg_replace('/\s+/', '', $data['customer_phone']);
        $order->update($data);

        return response()->json(['message' => 'Order updated.']);
    }
}
