<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('staff');
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->unsignedInteger('price_cents');
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('reorder_level')->default(5);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('company')->nullable();
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('confirmed')->index();
            $table->unsignedBigInteger('total_cents');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price_cents');
            $table->unique(['order_id', 'product_id']);
            $table->timestamps();
        });
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');
            $table->string('subject');
            $table->json('details')->nullable();
            $table->timestamps();
        });
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->string('status')->default('queued');
            $table->string('path')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['reports', 'activities', 'order_items', 'orders', 'customers', 'products'] as $table) {
            Schema::dropIfExists($table);
        } Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
