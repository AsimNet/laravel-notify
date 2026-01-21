<?php

namespace Asimnet\Notify\Tests\Feature;

use Asimnet\Notify\Services\SegmentResolver;
use Asimnet\Notify\Tests\TestCase;
use Asimnet\Notify\Tests\TestUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SegmentResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with various attributes
        $this->createUserWithAttributes(['name' => 'Ahmed', 'gender' => 'male', 'city' => 'Riyadh', 'dob' => '1990-01-15']);
        $this->createUserWithAttributes(['name' => 'Fatima', 'gender' => 'female', 'city' => 'Riyadh', 'dob' => '1985-06-20']);
        $this->createUserWithAttributes(['name' => 'Omar', 'gender' => 'male', 'city' => 'Jeddah', 'dob' => '2000-03-10']);
        $this->createUserWithAttributes(['name' => 'Sara', 'gender' => 'female', 'city' => 'Jeddah', 'dob' => '1995-09-25']);
        $this->createUserWithAttributes(['name' => 'Khalid', 'gender' => 'male', 'city' => 'Dammam', 'dob' => '1988-12-01']);
    }

    public function test_empty_conditions_returns_all_users(): void
    {
        $resolver = new SegmentResolver([]);

        $count = $resolver->getCount();

        $this->assertEquals(5, $count);
    }

    public function test_single_text_equals_condition(): void
    {
        $conditions = [
            'operator' => 'and',
            'conditions' => [
                ['field' => 'gender', 'filterType' => 'text', 'type' => 'equals', 'filter' => 'male'],
            ],
        ];

        $resolver = new SegmentResolver($conditions);
        $userIds = $resolver->getUserIds();

        $this->assertCount(3, $userIds); // Ahmed, Omar, Khalid
    }

    public function test_single_text_not_equals_condition(): void
    {
        $conditions = [
            'operator' => 'and',
            'conditions' => [
                ['field' => 'gender', 'filterType' => 'text', 'type' => 'notEqual', 'filter' => 'male'],
            ],
        ];

        $resolver = new SegmentResolver($conditions);

        $this->assertEquals(2, $resolver->getCount()); // Fatima, Sara
    }

    public function test_text_contains_condition(): void
    {
        // Skip test on SQLite (uses LIKE instead of ILIKE which is PostgreSQL-only)
        if (config('database.default') === 'testing') {
            $this->markTestSkipped('ilike is PostgreSQL-specific, cannot test on SQLite');
        }

        $conditions = [
            'operator' => 'and',
            'conditions' => [
                ['field' => 'city', 'filterType' => 'text', 'type' => 'contains', 'filter' => 'yadh'],
            ],
        ];

        $resolver = new SegmentResolver($conditions);

        $this->assertEquals(2, $resolver->getCount()); // Riyadh users
    }

    public function test_multiple_and_conditions_narrow_results(): void
    {
        $conditions = [
            'operator' => 'and',
            'conditions' => [
                ['field' => 'gender', 'filterType' => 'text', 'type' => 'equals', 'filter' => 'male'],
                ['field' => 'city', 'filterType' => 'text', 'type' => 'equals', 'filter' => 'Riyadh'],
            ],
        ];

        $resolver = new SegmentResolver($conditions);
        $userIds = $resolver->getUserIds();

        $this->assertCount(1, $userIds); // Only Ahmed: male AND Riyadh
    }

    public function test_or_conditions_expand_results(): void
    {
        $conditions = [
            'operator' => 'or',
            'conditions' => [
                ['field' => 'city', 'filterType' => 'text', 'type' => 'equals', 'filter' => 'Riyadh'],
                ['field' => 'city', 'filterType' => 'text', 'type' => 'equals', 'filter' => 'Jeddah'],
            ],
        ];

        $resolver = new SegmentResolver($conditions);

        $this->assertEquals(4, $resolver->getCount()); // Riyadh (2) + Jeddah (2)
    }

    public function test_nested_and_or_groups(): void
    {
        // Male AND (Riyadh OR Jeddah)
        $conditions = [
            'operator' => 'and',
            'conditions' => [
                ['field' => 'gender', 'filterType' => 'text', 'type' => 'equals', 'filter' => 'male'],
                [
                    'operator' => 'or',
                    'conditions' => [
                        ['field' => 'city', 'filterType' => 'text', 'type' => 'equals', 'filter' => 'Riyadh'],
                        ['field' => 'city', 'filterType' => 'text', 'type' => 'equals', 'filter' => 'Jeddah'],
                    ],
                ],
            ],
        ];

        $resolver = new SegmentResolver($conditions);
        $userIds = $resolver->getUserIds();

        $this->assertCount(2, $userIds); // Ahmed (male, Riyadh), Omar (male, Jeddah)
    }

    public function test_date_less_than_condition_for_age(): void
    {
        // Users born before 1994 (age > 30 as of 2024)
        $conditions = [
            'operator' => 'and',
            'conditions' => [
                ['field' => 'dob', 'filterType' => 'date', 'type' => 'lessThan', 'filter' => '1994-01-01'],
            ],
        ];

        $resolver = new SegmentResolver($conditions);
        $userIds = $resolver->getUserIds();

        // Ahmed (1990), Fatima (1985), Khalid (1988) are older than 30
        $this->assertCount(3, $userIds);
    }

    public function test_date_greater_than_condition(): void
    {
        // Users born after 1995
        $conditions = [
            'operator' => 'and',
            'conditions' => [
                ['field' => 'dob', 'filterType' => 'date', 'type' => 'greaterThan', 'filter' => '1995-01-01'],
            ],
        ];

        $resolver = new SegmentResolver($conditions);

        // Sara (1995-09-25) and Omar (2000-03-10)
        $this->assertEquals(2, $resolver->getCount());
    }

    public function test_number_greater_than_condition(): void
    {
        // Test with ID field (numeric column available in all tests)
        $conditions = [
            'operator' => 'and',
            'conditions' => [
                ['field' => 'id', 'filterType' => 'number', 'type' => 'greaterThan', 'filter' => 2],
            ],
        ];

        $resolver = new SegmentResolver($conditions);

        // IDs 3, 4, 5 should match
        $this->assertEquals(3, $resolver->getCount());
    }

    public function test_blank_condition(): void
    {
        // Create a user with null city
        $this->createUserWithAttributes(['name' => 'NoCity', 'city' => null]);

        $conditions = [
            'operator' => 'and',
            'conditions' => [
                ['field' => 'city', 'filterType' => 'text', 'type' => 'blank'],
            ],
        ];

        $resolver = new SegmentResolver($conditions);

        $this->assertEquals(1, $resolver->getCount()); // Only NoCity user
    }

    public function test_not_blank_condition(): void
    {
        // All 5 original users have city set
        $conditions = [
            'operator' => 'and',
            'conditions' => [
                ['field' => 'city', 'filterType' => 'text', 'type' => 'notBlank'],
            ],
        ];

        $resolver = new SegmentResolver($conditions);

        $this->assertEquals(5, $resolver->getCount());
    }

    public function test_complex_real_world_segment(): void
    {
        // "Male users over 30 in Riyadh or Jeddah"
        // male AND dob < 1994 AND (city = Riyadh OR city = Jeddah)
        $conditions = [
            'operator' => 'and',
            'conditions' => [
                ['field' => 'gender', 'filterType' => 'text', 'type' => 'equals', 'filter' => 'male'],
                ['field' => 'dob', 'filterType' => 'date', 'type' => 'lessThan', 'filter' => '1994-01-01'],
                [
                    'operator' => 'or',
                    'conditions' => [
                        ['field' => 'city', 'filterType' => 'text', 'type' => 'equals', 'filter' => 'Riyadh'],
                        ['field' => 'city', 'filterType' => 'text', 'type' => 'equals', 'filter' => 'Jeddah'],
                    ],
                ],
            ],
        ];

        $resolver = new SegmentResolver($conditions);
        $userIds = $resolver->getUserIds();

        // Ahmed: male, 1990 (>30), Riyadh - YES
        // Omar: male, 2000 (<30) - NO
        // Khalid: male, 1988 (>30), Dammam - NO (not Riyadh/Jeddah)
        $this->assertCount(1, $userIds);
    }

    public function test_get_query_returns_builder(): void
    {
        $conditions = [
            'operator' => 'and',
            'conditions' => [
                ['field' => 'gender', 'filterType' => 'text', 'type' => 'equals', 'filter' => 'male'],
            ],
        ];

        $resolver = new SegmentResolver($conditions);
        $query = $resolver->getQuery();

        $this->assertInstanceOf(Builder::class, $query);
    }

    public function test_text_starts_with_condition(): void
    {
        // Skip test on SQLite (uses LIKE instead of ILIKE which is PostgreSQL-only)
        if (config('database.default') === 'testing') {
            $this->markTestSkipped('ilike is PostgreSQL-specific, cannot test on SQLite');
        }

        $conditions = [
            'operator' => 'and',
            'conditions' => [
                ['field' => 'city', 'filterType' => 'text', 'type' => 'startsWith', 'filter' => 'Riy'],
            ],
        ];

        $resolver = new SegmentResolver($conditions);

        $this->assertEquals(2, $resolver->getCount()); // Riyadh users
    }

    public function test_text_ends_with_condition(): void
    {
        // Skip test on SQLite (uses LIKE instead of ILIKE which is PostgreSQL-only)
        if (config('database.default') === 'testing') {
            $this->markTestSkipped('ilike is PostgreSQL-specific, cannot test on SQLite');
        }

        $conditions = [
            'operator' => 'and',
            'conditions' => [
                ['field' => 'city', 'filterType' => 'text', 'type' => 'endsWith', 'filter' => 'dah'],
            ],
        ];

        $resolver = new SegmentResolver($conditions);

        $this->assertEquals(2, $resolver->getCount()); // Jeddah users
    }

    public function test_number_less_than_condition(): void
    {
        $conditions = [
            'operator' => 'and',
            'conditions' => [
                ['field' => 'id', 'filterType' => 'number', 'type' => 'lessThan', 'filter' => 3],
            ],
        ];

        $resolver = new SegmentResolver($conditions);

        $this->assertEquals(2, $resolver->getCount()); // IDs 1, 2
    }

    public function test_number_in_range_condition(): void
    {
        $conditions = [
            'operator' => 'and',
            'conditions' => [
                ['field' => 'id', 'filterType' => 'number', 'type' => 'inRange', 'filter' => 2, 'filterTo' => 4],
            ],
        ];

        $resolver = new SegmentResolver($conditions);

        $this->assertEquals(3, $resolver->getCount()); // IDs 2, 3, 4
    }

    public function test_date_equals_condition(): void
    {
        $conditions = [
            'operator' => 'and',
            'conditions' => [
                ['field' => 'dob', 'filterType' => 'date', 'type' => 'equals', 'filter' => '1990-01-15'],
            ],
        ];

        $resolver = new SegmentResolver($conditions);

        $this->assertEquals(1, $resolver->getCount()); // Ahmed only
    }

    public function test_date_in_range_condition(): void
    {
        $conditions = [
            'operator' => 'and',
            'conditions' => [
                ['field' => 'dob', 'filterType' => 'date', 'type' => 'inRange', 'filter' => '1988-01-01', 'filterTo' => '1995-12-31'],
            ],
        ];

        $resolver = new SegmentResolver($conditions);

        // Ahmed (1990), Khalid (1988), Sara (1995)
        $this->assertEquals(3, $resolver->getCount());
    }

    public function test_empty_conditions_array_returns_all_users(): void
    {
        $resolver = new SegmentResolver(['operator' => 'and', 'conditions' => []]);

        $this->assertEquals(5, $resolver->getCount());
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
