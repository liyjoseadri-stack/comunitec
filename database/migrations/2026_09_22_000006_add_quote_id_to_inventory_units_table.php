<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_units', fn (Blueprint $t) => $t->foreignId('quote_id')->nullable()->constrained()->nullOnDelete());
    }

    public function down(): void
    {
        Schema::table('inventory_units', fn (Blueprint $t) => $t->dropConstrainedForeignId('quote_id'));
    }
};
