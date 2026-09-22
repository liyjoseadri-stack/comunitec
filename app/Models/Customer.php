<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Customer extends Model { protected $fillable = ['type','name','rfc','email','phone','address','postal_code']; }
