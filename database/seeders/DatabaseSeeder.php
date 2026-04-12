<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Seller;
use App\Models\Driver;
use App\Models\Order;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin ──
        User::create([
            'name'     => 'Admin User',
            'email'    => 'admin@ldms.com',
            'password' => Hash::make('password'),
            'role'     => 'admin',
            'phone'    => '07501000001',
        ]);

        // ── Sellers ──
        $sellers = [
            ['name' => 'Sara Ahmad',   'email' => 'sara@ldms.com',   'biz' => 'Sara Boutique',    'addr' => 'Erbil, Ainkawa'],
            ['name' => 'Karwan Hassan','email' => 'karwan@ldms.com', 'biz' => 'Karwan Electronics','addr' => 'Sulaymaniyah Bazaar'],
            ['name' => 'Narin Omar',   'email' => 'narin@ldms.com',  'biz' => 'Narin Fashion',    'addr' => 'Duhok, Center'],
        ];

        $sellerModels = [];
        foreach ($sellers as $s) {
            $user = User::create([
                'name'     => $s['name'],
                'email'    => $s['email'],
                'password' => Hash::make('password'),
                'role'     => 'seller',
                'phone'    => '075010000' . rand(10, 99),
            ]);
            $sellerModels[] = Seller::create(['user_id' => $user->id, 'business_name' => $s['biz'], 'business_address' => $s['addr']]);
        }

        // ── Drivers ──
        $driversData = [
            ['name' => 'Ali Mahmoud', 'email' => 'ali@ldms.com',  'vehicle' => 'Motorcycle', 'plate' => 'KRG-1234'],
            ['name' => 'Hiwa Jalal',  'email' => 'hiwa@ldms.com', 'vehicle' => 'Car',        'plate' => 'KRG-5678'],
            ['name' => 'Dara Aziz',   'email' => 'dara@ldms.com', 'vehicle' => 'Van',        'plate' => 'KRG-9101'],
        ];

        $driverModels = [];
        foreach ($driversData as $d) {
            $user = User::create([
                'name'     => $d['name'],
                'email'    => $d['email'],
                'password' => Hash::make('password'),
                'role'     => 'driver',
                'phone'    => '075020000' . rand(10, 99),
            ]);
            $driverModels[] = Driver::create(['user_id' => $user->id, 'vehicle_type' => $d['vehicle'], 'vehicle_number' => $d['plate']]);
        }

        // ── Sample Orders ──
        $statuses = ['pending', 'assigned', 'picked_up', 'on_the_way', 'delivered', 'failed'];
        $zones    = ['Zone A - City Center', 'Zone B - Suburbs', 'Zone C - Industrial', null];

        for ($i = 0; $i < 30; $i++) {
            $status = $statuses[array_rand($statuses)];
            $seller = $sellerModels[array_rand($sellerModels)];
            $driver = $status !== 'pending' ? $driverModels[array_rand($driverModels)] : null;

            Order::create([
                'seller_id'           => $seller->id,
                'driver_id'           => $driver?->id,
                'customer_name'       => 'Customer ' . ($i + 1),
                'customer_phone'      => '075030000' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'delivery_address'    => 'Street ' . ($i + 1) . ', Erbil, Kurdistan',
                'product_description' => 'Product package #' . ($i + 1) . ' - Fragile items',
                'delivery_fee'        => rand(3000, 15000) / 100,
                'special_instructions'=> $i % 3 === 0 ? 'Call before delivery' : null,
                'delivery_zone'       => $zones[array_rand($zones)],
                'status'              => $status,
                'failure_reason'      => $status === 'failed' ? 'Customer was not available' : null,
                'assigned_at'         => $status !== 'pending' ? now()->subHours(rand(1, 48)) : null,
                'picked_up_at'        => in_array($status, ['picked_up', 'on_the_way', 'delivered']) ? now()->subHours(rand(1, 24)) : null,
                'delivered_at'        => $status === 'delivered' ? now()->subHours(rand(1, 12)) : null,
                'created_at'          => now()->subDays(rand(0, 30)),
            ]);
        }
    }
}
