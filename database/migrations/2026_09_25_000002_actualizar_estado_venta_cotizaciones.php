<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('cotizaciones')
            ->where('estado', 'se_hizo_venta')
            ->update(['estado' => 'venta']);

        DB::table('cotizaciones')
            ->whereIn('id', DB::table('ventas')->select('cotizacion_id'))
            ->update([
                'estado' => 'venta',
                'vence_en' => null,
            ]);
    }

    public function down(): void
    {
        DB::table('cotizaciones')
            ->where('estado', 'venta')
            ->update(['estado' => 'aceptada']);
    }
};
