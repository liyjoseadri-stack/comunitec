<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('customers', function (Blueprint $table) { $table->id(); $table->string('type'); $table->string('name'); $table->string('rfc')->unique(); $table->string('email'); $table->string('phone'); $table->string('address'); $table->string('postal_code', 10); $table->timestamps(); }); } public function down(): void { Schema::dropIfExists('customers'); } };
