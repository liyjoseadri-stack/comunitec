<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_email_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('recipient');
            $table->string('result');
            $table->string('message')->nullable();
            $table->timestamp('attempted_at');
            $table->timestamps();

            $table->index([
                'quote_id',
                'attempted_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_email_deliveries');
    }
};
