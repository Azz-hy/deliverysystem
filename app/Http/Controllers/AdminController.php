<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Order;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_sellers' => User::where('role', 'seller')->count(),
            'total_drivers' => User::where('role', 'driver')->count(),
            'total_orders' => Order::count(),
            'total_revenue' => Order::where('status', 'delivered')->sum('delivery_fee'),
        ];

        $ordersByStatus = Order::selectRaw('LOWER(status) as st, count(*) as count')
            ->groupBy('st')->pluck('count', 'st');

        $topDrivers = Driver::with('user')
            ->withCount(['orders as delivered_count' => fn ($q) => $q->whereRaw('LOWER(status) = ?', ['delivered'])])
            ->orderByDesc('delivered_count')->take(5)->get()
            ->map(fn ($d) => ['name' => $d->user->name, 'delivered_count' => $d->delivered_count]);

        $recentOrders = Order::with(['seller.user', 'driver.user'])
            ->latest()->take(10)->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'seller_name' => $o->seller?->user?->name,
                'customer_name' => $o->customer_name,
                'product_price' => $o->product_price,
                'delivery_fee' => $o->delivery_fee,
                'status' => $o->status,
                'created_at' => $o->created_at,
            ]);

        return response()->json([
            'stats' => $stats,
            'orders_by_status' => $ordersByStatus,
            'top_drivers' => $topDrivers,
            'recent_orders' => $recentOrders,
        ]);
    }

    public function users(Request $request)
    {
        $role = $request->get('role', 'seller');
        $users = User::with(['seller', 'driver'])
            ->where('role', $role)
            ->latest()->paginate(20);

        $mapped = $users->map(function ($u) {
            $base = ['id' => $u->id, 'name' => $u->name, 'email' => $u->email,
                'phone' => $u->phone, 'is_active' => $u->is_active, 'created_at' => $u->created_at];
            if ($u->isSeller()) {
                $base['business_name'] = $u->seller?->business_name;
                $base['business_address'] = $u->seller?->business_address;
            }
            if ($u->isDriver()) {
                $base['vehicle_type'] = $u->driver?->vehicle_type;
                $base['vehicle_number'] = $u->driver?->vehicle_number;
                $base['active_orders'] = $u->driver?->activeOrdersCount() ?? 0;
            }

            return $base;
        });

        return response()->json(['data' => $mapped, 'last_page' => $users->lastPage()]);
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users'],
            'phone' => ['nullable', 'string', 'regex:/^07[\d\s]{9,11}$/'],
            'role' => ['required', Rule::in(['seller', 'driver'])],
            'password' => ['required', 'confirmed', 'min:8'],
            'business_name' => ['nullable', 'string'],
            'business_address' => ['nullable', 'string'],
            'vehicle_type' => ['nullable', Rule::in(['Motorcycle', 'Car', 'Van'])],
            'vehicle_number' => ['nullable', 'string'],
        ]);

        if (! empty($data['phone'])) {
            $data['phone'] = preg_replace('/\s+/', '', $data['phone']);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        if ($data['role'] === 'seller') {
            Seller::create(['user_id' => $user->id, 'business_name' => $data['business_name'] ?? null, 'business_address' => $data['business_address'] ?? null]);
        } else {
            Driver::create(['user_id' => $user->id, 'vehicle_type' => $data['vehicle_type'] ?? null, 'vehicle_number' => $data['vehicle_number'] ?? null]);
        }

        return response()->json(['message' => 'User created.', 'id' => $user->id], 201);
    }

    public function toggleUser(User $user)
    {
        $user->update(['is_active' => ! $user->is_active]);

        return response()->json(['is_active' => $user->is_active]);
    }

    public function deleteUser(User $user)
    {
        if ($user->role === 'admin') {
            return response()->json(['message' => 'Cannot delete admin users.'], 403);
        }

        if ($user->role === 'seller') {
            Seller::where('user_id', $user->id)->delete();
        } elseif ($user->role === 'driver') {
            Driver::where('user_id', $user->id)->delete();
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }

    public function driversList()
    {
        return response()->json(
            Driver::with('user')->get()->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->user->name,
                'active_orders' => $d->activeOrdersCount(),
            ])
        );
    }

    public function orders(Request $request)
    {
        $query = Order::with(['seller.user', 'driver.user']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('order_number', 'like', "%$s%")
                ->orWhere('customer_name', 'like', "%$s%")
                ->orWhere('customer_phone', 'like', "%$s%"));
        }

        $orders = $query->latest()->paginate(15);

        $mapped = $orders->map(fn ($o) => [
            'id' => $o->id,
            'order_number' => $o->order_number,
            'seller_name' => $o->seller?->user?->name,
            'customer_name' => $o->customer_name,
            'customer_phone' => $o->customer_phone,
            'driver_name' => $o->driver?->user?->name,
            'product_price' => $o->product_price,
            'delivery_fee' => $o->delivery_fee,
            'status' => $o->status,
            'created_at' => $o->created_at,
        ]);

        return response()->json(['data' => $mapped, 'last_page' => $orders->lastPage()]);
    }

    public function showOrder(Order $order)
    {
        $order->load(['seller.user', 'driver.user']);

        return response()->json([
            'id' => $order->id,
            'order_number' => $order->order_number,
            'seller_name' => $order->seller?->user?->name,
            'driver_id' => $order->driver_id,
            'driver_name' => $order->driver?->user?->name,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'delivery_address' => $order->delivery_address,
            'product_description' => $order->product_description,
            'product_price' => $order->product_price,
            'delivery_fee' => $order->delivery_fee,
            'special_instructions' => $order->special_instructions,
            'status' => $order->status,
            'failure_reason' => $order->failure_reason,
            'driver_notes' => $order->driver_notes,
            'created_at' => $order->created_at,
        ]);
    }

    public function assignDriver(Request $request, Order $order)
    {
        $request->validate(['driver_id' => ['required', 'exists:drivers,id']]);

        if ($order->isFinal()) {
            return response()->json(['message' => 'Cannot modify a finalized order.'], 422);
        }

        $order->update(['driver_id' => $request->driver_id, 'status' => 'assigned', 'assigned_at' => now()]);

        return response()->json(['message' => 'Driver assigned.']);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate(['status' => ['required', 'string']]);

        if (! $order->canTransitionTo($request->status)) {
            return response()->json(['message' => 'Invalid status transition.'], 422);
        }

        $update = ['status' => $request->status];
        if ($request->status === 'failed') {
            $update['failure_reason'] = $request->failure_reason;
        }
        if ($request->status === 'delivered') {
            $update['delivered_at'] = now();
        }

        $order->update($update);

        return response()->json(['message' => 'Status updated.']);
    }

    public function reports(Request $request)
    {
        $type = $request->get('type', 'daily');

        if ($type === 'daily') {
            $date = $request->get('date', today()->toDateString());

            return response()->json([
                'total_created' => Order::whereDate('created_at', $date)->count(),
                'delivered' => Order::where('status', 'delivered')->whereDate('delivered_at', $date)->count(),
                'failed' => Order::where('status', 'failed')->whereDate('updated_at', $date)->count(),
                'revenue' => Order::where('status', 'delivered')->whereDate('delivered_at', $date)->sum('delivery_fee'),
            ]);
        }

        if ($type === 'monthly') {
            return response()->json(
                Order::selectRaw("strftime('%Y-%m', created_at) as month, COUNT(*) as orders, SUM(CASE WHEN status='delivered' THEN delivery_fee ELSE 0 END) as revenue, SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) as completed")
                    ->groupBy('month')->orderByDesc('month')->take(12)->get()
            );
        }

        if ($type === 'seller') {
            return response()->json(
                Seller::with('user')
                    ->withCount(['orders as total', 'orders as delivered' => fn ($q) => $q->where('status', 'delivered'), 'orders as failed' => fn ($q) => $q->where('status', 'failed')])
                    ->withSum('orders', 'delivery_fee')
                    ->orderByDesc('total')->get()
                    ->map(fn ($s) => ['name' => $s->user->name, 'total' => $s->total, 'delivered' => $s->delivered, 'failed' => $s->failed, 'total_spent' => $s->orders_sum_delivery_fee ?? 0])
            );
        }

        if ($type === 'driver') {
            return response()->json(
                Driver::with('user')
                    ->withCount(['orders as total', 'orders as delivered' => fn ($q) => $q->where('status', 'delivered'), 'orders as failed' => fn ($q) => $q->where('status', 'failed')])
                    ->orderByDesc('delivered')->get()
                    ->map(fn ($d) => ['name' => $d->user->name, 'vehicle' => $d->vehicle_type, 'total' => $d->total, 'delivered' => $d->delivered, 'failed' => $d->failed, 'rate' => $d->total > 0 ? round($d->delivered / $d->total * 100, 1) : 0])
            );
        }

        return response()->json([]);
    }
}
