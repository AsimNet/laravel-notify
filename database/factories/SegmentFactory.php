<?php

namespace Asimnet\Notify\Database\Factories;

use Asimnet\Notify\Models\Segment;
use Illuminate\Database\Eloquent\Factories\Factory;

class SegmentFactory extends Factory
{
    protected $model = Segment::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'name' => $this->faker->words(3, true),
            'slug' => $this->faker->unique()->slug(2),
            'description' => $this->faker->sentence(),
            'conditions' => [
                'operator' => 'and',
                'conditions' => [],
            ],
            'is_active' => true,
            'cached_count' => null,
            'cached_at' => null,
        ];
    }

    /**
     * Set segment as inactive.
     *
     * تعيين الشريحة كغير نشطة.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set a specific slug.
     *
     * تعيين معرف محدد.
     */
    public function slug(string $slug): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => $slug,
        ]);
    }

    /**
     * Set a specific description.
     *
     * تعيين وصف محدد.
     */
    public function withDescription(string $description): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => $description,
        ]);
    }

    /**
     * Create segment for users over 30 years old.
     *
     * إنشاء شريحة للمستخدمين فوق 30 عاماً.
     *
     * Uses dob (date of birth) field with lessThan date filter.
     */
    public function ageOver30(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Users Over 30',
            'slug' => 'users-over-30',
            'description' => 'Users who are older than 30 years',
            'conditions' => [
                'operator' => 'and',
                'conditions' => [
                    [
                        'field' => 'dob',
                        'filterType' => 'date',
                        'type' => 'lessThan',
                        'filter' => now()->subYears(30)->format('Y-m-d'),
                    ],
                ],
            ],
        ]);
    }

    /**
     * Create segment for male users.
     *
     * إنشاء شريحة للمستخدمين الذكور.
     */
    public function genderMale(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Male Users',
            'slug' => 'male-users',
            'description' => 'Users with gender set to male',
            'conditions' => [
                'operator' => 'and',
                'conditions' => [
                    [
                        'field' => 'gender',
                        'filterType' => 'text',
                        'type' => 'equals',
                        'filter' => 'male',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Create segment for users in Riyadh.
     *
     * إنشاء شريحة للمستخدمين في الرياض.
     */
    public function cityRiyadh(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Riyadh Users',
            'slug' => 'riyadh-users',
            'description' => 'Users located in Riyadh city',
            'conditions' => [
                'operator' => 'and',
                'conditions' => [
                    [
                        'field' => 'city',
                        'filterType' => 'text',
                        'type' => 'equals',
                        'filter' => 'Riyadh',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Create segment with complex nested AND/OR conditions.
     *
     * إنشاء شريحة مع شروط AND/OR متداخلة معقدة.
     *
     * Males in Riyadh OR Jeddah.
     */
    public function complex(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Complex Segment',
            'slug' => 'complex-segment',
            'description' => 'Males in Riyadh or Jeddah',
            'conditions' => [
                'operator' => 'and',
                'conditions' => [
                    [
                        'field' => 'gender',
                        'filterType' => 'text',
                        'type' => 'equals',
                        'filter' => 'male',
                    ],
                    [
                        'operator' => 'or',
                        'conditions' => [
                            [
                                'field' => 'city',
                                'filterType' => 'text',
                                'type' => 'equals',
                                'filter' => 'Riyadh',
                            ],
                            [
                                'field' => 'city',
                                'filterType' => 'text',
                                'type' => 'equals',
                                'filter' => 'Jeddah',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Set specific conditions.
     *
     * تعيين شروط محددة.
     *
     * @param  array<string, mixed>  $conditions
     */
    public function withConditions(array $conditions): static
    {
        return $this->state(fn (array $attributes) => [
            'conditions' => $conditions,
        ]);
    }

    /**
     * Set cached count values.
     *
     * تعيين قيم العدد المخزن مؤقتاً.
     */
    public function withCachedCount(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'cached_count' => $count,
            'cached_at' => now(),
        ]);
    }
}
