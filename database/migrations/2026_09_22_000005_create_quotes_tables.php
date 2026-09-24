<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $t) {
            $t->id();
            $t->string('folio')->unique();
            $t->foreignId('customer_id')->constrained();
            $t->foreignId('user_id')->constrained();
            $t->string('area_requesting')->nullable();
            $t->string('status')->default('draft');
            $t->timestamp('sent_at')->nullable();
            $t->timestamp('accepted_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->decimal('discount_percent', 5, 2)->default(0);
            $t->decimal('total', 12, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('quote_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $t->foreignId('catalog_item_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type');
            $t->string('description');
            $t->decimal('quantity', 10, 2);
            $t->decimal('unit_price', 12, 2);
            $t->decimal('subtotal', 12, 2);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_lines');
        Schema::dropIfExists('quotes');
    }
};
