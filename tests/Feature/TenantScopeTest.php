<?php

namespace Asimnet\Notify\Tests\Feature;

use Asimnet\Notify\Models\Concerns\BelongsToTenantOptionally;
use Asimnet\Notify\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Test model using the BelongsToTenantOptionally trait.
 */
class TestTenantModel extends Model
{
    use BelongsToTenantOptionally;

    protected $table = 'test_tenant_models';

    protected $guarded = [];
}

class TenantScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create a test table for the test model
        Schema::create('test_tenant_models', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->string('name');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('test_tenant_models');

        parent::tearDown();
    }

    /** @test */
    public function when_tenancy_disabled_all_records_are_returned(): void
    {
        $this->disableTenancy();

        // Create records with different tenant IDs
        TestTenantModel::create(['name' => __('Record 1'), 'tenant_id' => 'tenant-1']);
        TestTenantModel::create(['name' => __('Record 2'), 'tenant_id' => 'tenant-2']);
        TestTenantModel::create(['name' => __('Record 3'), 'tenant_id' => null]);

        // All records should be returned when tenancy is disabled
        $this->assertCount(3, TestTenantModel::all());
    }

    /** @test */
    public function when_tenancy_enabled_but_no_tenant_context_all_records_returned(): void
    {
        $this->enableTenancy();

        // Create records - note: we need to insert directly to bypass the trait boot
        // since there's no tenant context, tenant_id should remain as set
        \DB::table('test_tenant_models')->insert([
            ['name' => __('Record 1'), 'tenant_id' => 'tenant-1', 'created_at' => now(), 'updated_at' => now()],
            ['name' => __('Record 2'), 'tenant_id' => 'tenant-2', 'created_at' => now(), 'updated_at' => now()],
            ['name' => __('Record 3'), 'tenant_id' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Without tenant context, getCurrentTenantId returns null
        // so no filtering should happen
        $model = new TestTenantModel;
        $this->assertNull($model->getCurrentTenantId());

        // All records should be returned when no tenant context
        $this->assertCount(3, TestTenantModel::all());
    }

    /** @test */
    public function without_tenancy_method_returns_all_records(): void
    {
        $this->enableTenancy();

        // Insert records directly
        \DB::table('test_tenant_models')->insert([
            ['name' => __('Record 1'), 'tenant_id' => 'tenant-1', 'created_at' => now(), 'updated_at' => now()],
            ['name' => __('Record 2'), 'tenant_id' => 'tenant-2', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // withoutTenancy should return all records
        $records = TestTenantModel::withoutTenancy()->get();

        $this->assertCount(2, $records);
    }

    /** @test */
    public function for_tenant_scope_filters_by_specific_tenant(): void
    {
        $this->disableTenancy();

        // Create records
        TestTenantModel::create(['name' => __('Record 1'), 'tenant_id' => 'tenant-1']);
        TestTenantModel::create(['name' => __('Record 2'), 'tenant_id' => 'tenant-1']);
        TestTenantModel::create(['name' => __('Record 3'), 'tenant_id' => 'tenant-2']);

        // forTenant scope should filter
        $tenant1Records = TestTenantModel::forTenant('tenant-1')->get();
        $tenant2Records = TestTenantModel::forTenant('tenant-2')->get();

        $this->assertCount(2, $tenant1Records);
        $this->assertCount(1, $tenant2Records);
    }

    /** @test */
    public function for_tenant_scope_with_null_returns_all_records(): void
    {
        $this->disableTenancy();

        TestTenantModel::create(['name' => __('Record 1'), 'tenant_id' => 'tenant-1']);
        TestTenantModel::create(['name' => __('Record 2'), 'tenant_id' => 'tenant-2']);

        // forTenant with null should return all records
        $records = TestTenantModel::forTenant(null)->get();

        $this->assertCount(2, $records);
    }

    /** @test */
    public function get_tenant_id_column_returns_correct_column(): void
    {
        $model = new TestTenantModel;

        $this->assertEquals('tenant_id', $model->getTenantIdColumn());
    }

    /** @test */
    public function get_current_tenant_id_returns_null_when_tenancy_disabled(): void
    {
        $this->disableTenancy();

        $model = new TestTenantModel;

        $this->assertNull($model->getCurrentTenantId());
    }

    /** @test */
    public function get_current_tenant_id_returns_null_when_tenant_function_not_exists(): void
    {
        $this->enableTenancy();

        // Since Stancl/Tenancy is not installed in tests,
        // function_exists('tenant') should be false
        // or tenant() should return null
        $model = new TestTenantModel;

        $this->assertNull($model->getCurrentTenantId());
    }

    /** @test */
    public function creating_record_without_tenant_context_leaves_tenant_id_null(): void
    {
        $this->enableTenancy();

        // Without tenant context, tenant_id should remain null
        $record = TestTenantModel::create(['name' => __('Test Record')]);

        $this->assertNull($record->tenant_id);
    }

    /** @test */
    public function creating_record_with_explicit_tenant_id_preserves_it(): void
    {
        $this->enableTenancy();

        // Explicit tenant_id should be preserved
        $record = TestTenantModel::create([
            'name' => __('Test Record'),
            'tenant_id' => 'explicit-tenant',
        ]);

        $this->assertEquals('explicit-tenant', $record->tenant_id);
    }
}
