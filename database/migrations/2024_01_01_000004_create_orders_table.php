<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('customer_name', 100);
            $table->string('customer_phone', 20);
            $table->text('delivery_address');
            $table->text('product_description');
            $table->decimal('delivery_fee', 10, 2);
            $table->text('special_instructions')->nullable();
            $table->string('delivery_zone', 100)->nullable();
            $table->enum('status', ['pending', 'assigned', 'picked_up', 'on_the_way', 'delivered', 'failed'])
                  ->default('pending');
            $table->text('failure_reason')->nullable();
            $table->text('driver_notes')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
