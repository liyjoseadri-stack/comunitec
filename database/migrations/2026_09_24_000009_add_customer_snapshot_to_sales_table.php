<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('customer_type')->after('customer_id');
            $table->string('customer_name')->after('customer_type');
            $table->string('customer_rfc')->after('customer_name');
            $table->string('customer_email')->after('customer_rfc');
            $table->string('customer_phone')->after('customer_email');
            $table->string('customer_address')->after('customer_phone');
            $table->string('customer_postal_code', 10)->after('customer_address');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'customer_type',
                'customer_name',
                'customer_rfc',
                'customer_email',
                'customer_phone',
                'customer_address',
                'customer_postal_code',
            ]);
        });
    }
};
