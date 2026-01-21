<?php

namespace Asimnet\Notify\Tests\Feature;

use Asimnet\Notify\Models\Segment;
use Asimnet\Notify\Tests\TestCase;
use Asimnet\Notify\Tests\TestUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SegmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_segment_can_be_created_with_required_fields(): void
    {
        $segment = Segment::create([
            'name' => 'Test Segment',
            'conditions' => [
                'operator' => 'and',
                'conditions' => [],
            ],
        ]);

        $this->assertDatabaseHas(config('notify.tables.segments'), [
            'name' => 'Test Segment',
            'slug' => 'test-segment',
            'is_active' => true,
        ]);
    }

    public function test_slug_is_auto_generated_from_name(): void
    {
        $segment = Segment::create([
            'name' => 'My Custom Segment Name',
            'conditions' => ['operator' => 'and', 'conditions' => []],
        ]);

        $this->assertEquals('my-custom-segment-name', $segment->slug);
    }

    public function test_explicit_slug_is_not_overwritten(): void
    {
        $segment = Segment::create([
            'name' => 'Test Segment',
            'slug' => 'custom-slug',
            'conditions' => ['operator' => 'and', 'conditions' => []],
        ]);

        $this->assertEquals('custom-slug', $segment->slug);
    }

    public function test_conditions_are_cast_to_array(): void
    {
        $conditions = [
            'operator' => 'and',
            'conditions' => [
                ['field' => 'city', 'filterType' => 'text', 'type' => 'equals', 'filter' => 'Riyadh'],
            ],
        ];

        $segment = Segment::create([
            'name' => 'City Segment',
            'conditions' => $conditions,
        ]);

        $segment->refresh();

        $this->assertIsArray($segment->conditions);
        $this->assertEquals('and', $segment->conditions['operator']);
    }

    public function test_active_scope_filters_inactive_segments(): void
    {
        Segment::factory()->create(['name' => 'Active Segment', 'is_active' => true]);
        Segment::factory()->inactive()->create(['name' => 'Inactive Segment']);

        $active = Segment::active()->get();

        $this->assertCount(1, $active);
        $this->assertEquals('Active Segment', $active->first()->name);
    }

    public function test_by_slug_scope_finds_segment(): void
    {
        Segment::factory()->slug('test-segment')->create();
        Segment::factory()->slug('other-segment')->create();

        $segment = Segment::bySlug('test-segment')->first();

        $this->assertNotNull($segment);
        $this->assertEquals('test-segment', $segment->slug);
    }

    public function test_cached_count_can_be_refreshed(): void
    {
        // Create users to match
        $this->createUserWithAttributes(['gender' => 'male']);
        $this->createUserWithAttributes(['gender' => 'male']);
        $this->createUserWithAttributes(['gender' => 'female']);

        $segment = Segment::factory()->genderMale()->create();

        $count = $segment->refreshCachedCount();

        $this->assertEquals(2, $count);
        $this->assertEquals(2, $segment->fresh()->cached_count);
        $this->assertNotNull($segment->fresh()->cached_at);
    }

    public function test_factory_states_create_valid_conditions(): void
    {
        $ageSegment = Segment::factory()->ageOver30()->create();
        $genderSegment = Segment::factory()->genderMale()->create();
        $citySegment = Segment::factory()->cityRiyadh()->create();
        $complexSegment = Segment::factory()->complex()->create();

        $this->assertIsArray($ageSegment->conditions);
        $this->assertEquals('and', $ageSegment->conditions['operator']);

        $this->assertCount(1, $genderSegment->conditions['conditions']);
        $this->assertEquals('gender', $genderSegment->conditions['conditions'][0]['field']);

        $this->assertEquals('city', $citySegment->conditions['conditions'][0]['field']);
        $this->assertEquals('Riyadh', $citySegment->conditions['conditions'][0]['filter']);

        // Complex has nested OR group
        $this->assertCount(2, $complexSegment->conditions['conditions']);
        $this->assertEquals('or', $complexSegment->conditions['conditions'][1]['operator']);
    }

    public function test_has_conditions_returns_true_when_conditions_exist(): void
    {
        $segment = Segment::factory()->genderMale()->create();

        $this->assertTrue($segment->hasConditions());
    }

    public function test_has_conditions_returns_false_when_empty(): void
    {
        $segment = Segment::factory()->create([
            'conditions' => ['operator' => 'and', 'conditions' => []],
        ]);

        $this->assertFalse($segment->hasConditions());
    }

    public function test_get_empty_conditions_returns_correct_structure(): void
    {
        $empty = Segment::getEmptyConditions();

        $this->assertEquals([
            'operator' => 'and',
            'conditions' => [],
        ], $empty);
    }

    /**
     * Helper to create a user with specific attributes.
     */
    protected function createUserWithAttributes(array $attributes): TestUser
    {
        return TestUser::forceCreate(array_merge([
            'name' => 'Test User '.rand(1, 9999),
            'email' => 'test'.rand(1, 99999).'@example.com',
            'password' => bcrypt('password'),
        ], $attributes));
    }
}
