<?php
namespace Tests\Feature;
use App\Models\User; use Illuminate\Foundation\Testing\RefreshDatabase; use Tests\TestCase;
class CatalogTest extends TestCase { use RefreshDatabase; public function test_commercial_user_can_create_a_product(): void { $user=User::factory()->create(['role'=>User::ROLE_COMMERCIAL]); $this->actingAs($user)->post('/catalogo',[ 'type'=>'product','name'=>'Laptop','code'=>'LAP-001','brand'=>'Dell','model'=>'Latitude','unit'=>'pieza','price'=>'12000.00','stock'=>3 ])->assertRedirect('/catalogo'); $this->assertDatabaseHas('catalog_items',['code'=>'LAP-001','type'=>'product']); } }
