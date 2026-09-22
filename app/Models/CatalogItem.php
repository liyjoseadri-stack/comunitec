<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class CatalogItem extends Model { protected $fillable=['type','name','code','category_id','brand','model','unit','price','stock','active']; protected function casts():array{return ['price'=>'decimal:2','active'=>'boolean'];} public function category(){return $this->belongsTo(Category::class);} public function inventoryUnits(){return $this->hasMany(InventoryUnit::class);} }
