<?php
namespace Tests\Feature;
use App\Models\CatalogItem; use App\Models\User; use Illuminate\Foundation\Testing\RefreshDatabase; use Tests\TestCase;
class InventoryTest extends TestCase { use RefreshDatabase; public function test_commercial_user_can_register_a_serialized_piece(): void { $user=User::factory()->create(['role'=>User::ROLE_COMMERCIAL]); $item=CatalogItem::create(['type'=>'product','name'=>'Equipo','code'=>'EQ-01','unit'=>'pieza','price'=>100,'stock'=>0]); $this->actingAs($user)->post('/inventario/series',['catalog_item_id'=>$item->id,'serial_number'=>'SN-001'])->assertRedirect(); $this->assertDatabaseHas('inventory_units',['catalog_item_id'=>$item->id,'serial_number'=>'SN-001','status'=>'available']); } }
