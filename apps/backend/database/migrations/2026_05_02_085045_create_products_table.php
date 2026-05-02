<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->string('name');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->integer('stock_quantity')->default(0);
            $table->integer('min_stock')->default(0);
            $table->integer('max_stock')->default(999999999);
            $table->string('unit', 50)->default('cái');
            $table->decimal('weight', 12, 3)->nullable();
            $table->text('description')->nullable();
            $table->json('images')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['name', 'product_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
