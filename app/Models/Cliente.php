<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'customers';

    protected $fillable = [
        'type',
        'name',
        'rfc',
        'email',
        'phone',
        'address',
        'postal_code',
    ];
}
