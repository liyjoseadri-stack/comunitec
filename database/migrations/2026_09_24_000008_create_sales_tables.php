<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique();
            $table->foreignId('quote_id')->unique()->constrained('quotes');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('sold_at');
            $table->string('payment_method');
            $table->string('payment_method_detail')->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_percent', 5, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });

        Schema::create('sale_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('quote_line_id')->unique()->constrained('quote_lines');
            $table->foreignId('catalog_item_id')->nullable()->constrained('catalog_items')->nullOnDelete();
            $table->string('type');
            $table->string('description');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });

        Schema::table('inventory_units', function (Blueprint $table) {
            $table->foreignId('sale_line_id')
                ->nullable()
                ->constrained('sale_lines')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sale_line_id');
        });
        Schema::dropIfExists('sale_lines');
        Schema::dropIfExists('sales');
    }
};
