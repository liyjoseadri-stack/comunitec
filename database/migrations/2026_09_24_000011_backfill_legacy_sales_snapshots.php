<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('sales')
            ->orderBy('id')
            ->chunkById(100, function ($ventas): void {
                foreach ($ventas as $venta) {
                    $cliente = DB::table('customers')->find($venta->customer_id);
                    $responsable = DB::table('users')->find($venta->user_id);
                    $datos = $this->datosFaltantes($venta, $cliente, $responsable);

                    if ($datos !== []) {
                        DB::table('sales')
                            ->where('id', $venta->id)
                            ->update($datos);
                    }
                }
            });
    }

    public function down(): void
    {
        // Las copias recuperadas no se eliminan porque ya forman parte del historial.
    }

    private function datosFaltantes(object $venta, ?object $cliente, ?object $responsable): array
    {
        $datos = [];
        $camposCliente = [
            'customer_type' => 'type',
            'customer_name' => 'name',
            'customer_rfc' => 'rfc',
            'customer_email' => 'email',
            'customer_phone' => 'phone',
            'customer_address' => 'address',
            'customer_postal_code' => 'postal_code',
        ];
        foreach ($camposCliente as $destino => $origen) {
            if (($venta->{$destino} ?? '') === '' && $cliente !== null) {
                $datos[$destino] = $cliente->{$origen};
            }
        }
        if (($venta->responsible_name ?? '') === '' && $responsable !== null) {
            $datos['responsible_name'] = $responsable->name;
        }
        if (($venta->responsible_email ?? '') === '' && $responsable !== null) {
            $datos['responsible_email'] = $responsable->email;
        }

        return $datos;
    }
};
