<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    private function driver()
    {
        return auth()->user()->driver;
    }

    public function dashboard()
    {
        $d = $this->driver();

        return response()->json([
            'stats' => [
                'assigned' => $d->activeOrdersCount(),
                'today' => $d->completedToday(),
                'this_week' => $d->completedThisWeek(),
                'total' => $d->totalCompleted(),
                'success_rate' => $d->successRate(),
            ],
            'active_orders' => $d->orders()->with('seller.user')
                ->whereNotIn('status', ['delivered', 'failed'])
                ->latest()->get()
                ->map(fn ($o) => [
                    'id' => $o->id,
                    'order_number' => $o->order_number,
                    'customer_name' => $o->customer_name,
                    'customer_phone' => $o->customer_phone,
                    'delivery_address' => $o->delivery_address,
                    'product_price' => $o->product_price,
                    'seller_name' => $o->seller?->user?->name,
                    'delivery_fee' => $o->delivery_fee,
                    'status' => $o->status,
                ]),
        ]);
    }

    public function active(Request $request)
    {
        return response()->json(
            $this->driver()->orders()->with('seller.user')
                ->whereNotIn('status', ['delivered', 'failed'])
                ->latest()->get()
                ->map(fn ($o) => [
                    'id' => $o->id,
                    'order_number' => $o->order_number,
                    'customer_name' => $o->customer_name,
                    'customer_phone' => $o->customer_phone,
                    'delivery_address' => $o->delivery_address,
                    'product_price' => $o->product_price,
                    'seller_name' => $o->seller?->user?->name,
                    'delivery_fee' => $o->delivery_fee,
                    'status' => $o->status,
                ])
        );
    }

    public function history(Request $request)
    {
        $query = $this->driver()->orders()->with('seller.user')
            ->whereIn('status', ['delivered', 'failed']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(15);

        return response()->json([
            'data' => $orders->map(fn ($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'customer_name' => $o->customer_name,
                'product_price' => $o->product_price,
                'delivery_fee' => $o->delivery_fee,
                'status' => $o->status,
                'delivered_at' => $o->delivered_at,
                'updated_at' => $o->updated_at,
            ]),
            'last_page' => $orders->lastPage(),
        ]);
    }

    public function show(Order $order)
    {
        if ($order->driver_id !== $this->driver()->id) {
            abort(403);
        }
        $order->load('seller.user');

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
            'seller_name' => $order->seller?->user?->name,
            'status' => $order->status,
            'failure_reason' => $order->failure_reason,
            'driver_notes' => $order->driver_notes,
            'delivered_at' => $order->delivered_at,
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        if ($order->driver_id !== $this->driver()->id) {
            abort(403);
        }

        $request->validate(['status' => ['required', 'string']]);

        if (! $order->canTransitionTo($request->status)) {
            return response()->json(['message' => 'Invalid status transition.'], 422);
        }

        $update = ['status' => $request->status];
        if ($request->filled('driver_notes')) {
            $update['driver_notes'] = $request->driver_notes;
        }
        if ($request->status === 'failed') {
            $update['failure_reason'] = $request->failure_reason;
        }
        if ($request->status === 'picked_up') {
            $update['picked_up_at'] = now();
        }
        if ($request->status === 'delivered') {
            $update['delivered_at'] = now();
        }

        $order->update($update);

        return response()->json(['message' => 'Status updated to: '.$request->status]);
    }

    public function available()
    {
        return response()->json(
            Order::with('seller.user')
                ->where('status', 'pending')
                ->whereNull('driver_id')
                ->latest()->get()
                ->map(fn ($o) => [
                    'id' => $o->id,
                    'order_number' => $o->order_number,
                    'customer_name' => $o->customer_name,
                    'delivery_address' => $o->delivery_address,
                    'product_price' => $o->product_price,
                    'seller_name' => $o->seller?->user?->name,
                    'delivery_fee' => $o->delivery_fee,
                    'status' => $o->status,
                ])
        );
    }

    public function take(Order $order)
    {
        if ($order->status !== 'pending' || $order->driver_id !== null) {
            return response()->json(['message' => 'This order is no longer available.'], 422);
        }

        $order->update([
            'driver_id' => $this->driver()->id,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        return response()->json(['message' => 'Order taken successfully!']);
    }
}
