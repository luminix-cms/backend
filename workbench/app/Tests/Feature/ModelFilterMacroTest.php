<?php

namespace Workbench\App\Tests\Feature;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Luminix\Backend\Services\ModelFilter;
use Workbench\App\Models\Category;
use Workbench\App\Tests\TestCase;

/**
 * Custom operators registered through ModelFilter::macro().
 *
 * Seeded categories (ids 1-6): Important, Urgent, Personal, Work, School, Home
 */
class ModelFilterMacroTest extends TestCase
{
    protected function tearDown(): void
    {
        ModelFilter::flushMacros();

        parent::tearDown();
    }

    public function test_registered_macro_is_listed_as_operator()
    {
        ModelFilter::macro('startsWithLetter', function (Builder $query, string $column, mixed $value) {
            return $query->where($column, 'like', $value . '%');
        });

        $this->assertContains('startsWithLetter', ModelFilter::operators());
    }

    public function test_registered_macro_is_applied_to_the_query()
    {
        ModelFilter::macro('startsWithLetter', function (Builder $query, string $column, mixed $value) {
            return $query->where($column, 'like', $value . '%');
        });

        $results = (new ModelFilter(Category::class, ['name:startsWithLetter' => 'P']))
            ->apply(Category::query())
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Personal', $results->first()->name);
    }

    public function test_native_operators_and_macros_coexist()
    {
        ModelFilter::macro('startsWithLetter', function (Builder $query, string $column, mixed $value) {
            return $query->where($column, 'like', $value . '%');
        });

        $operators = ModelFilter::operators();

        $this->assertContains('equals', $operators);
        $this->assertContains('relation', $operators);
        $this->assertContains('like', $operators);
        $this->assertContains('startsWithLetter', $operators);
    }

    public function test_every_registered_macro_is_listed_regardless_of_how_many()
    {
        $names = [];

        for ($i = 1; $i <= 17; $i++) {
            $names[] = 'macro' . $i;

            ModelFilter::macro('macro' . $i, function (Builder $query, string $column, mixed $value) {
                return $query->where($column, $value);
            });
        }

        $operators = ModelFilter::operators();

        foreach ($names as $name) {
            $this->assertContains($name, $operators);
        }
    }
}
