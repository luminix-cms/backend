<?php

namespace Workbench\App\Tests\Feature;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Luminix\Backend\Exceptions\InvalidFilterException;
use Luminix\Backend\Services\ModelFilter;
use ReflectionMethod;
use ReflectionProperty;
use Workbench\App\Models\Category;
use Workbench\App\Models\ToDo;
use Workbench\App\Models\User;
use Workbench\App\Tests\TestCase;

/**
 * Unit tests for ModelFilter operators.
 *
 * Seeded categories (ids 1-6): Important, Urgent, Personal, Work, School, Home
 */
class ModelFilterUnitTest extends TestCase
{
    protected function tearDown(): void
    {
        // Model::resolveRelationUsing() stores resolvers in a static property
        // shared across the whole run; reset it so tests don't bleed into each other.
        $property = new ReflectionProperty(Model::class, 'relationResolvers');
        $property->setAccessible(true);
        $property->setValue(null, []);

        parent::tearDown();
    }

    // ── Equality ────────────────────────────────────────────────────────────

    public function test_equals_operator_matches_single_value()
    {
        $results = $this->filter(Category::class, ['name' => 'Important']);

        $this->assertCount(1, $results);
        $this->assertEquals('Important', $results->first()->name);
    }

    public function test_equals_operator_with_array_becomes_where_in()
    {
        $results = $this->filter(Category::class, ['name' => ['Important', 'Urgent']]);

        $this->assertCount(2, $results);
    }

    public function test_not_equals_operator_excludes_value()
    {
        $results = $this->filter(Category::class, ['name:notEquals' => 'Important']);

        $this->assertCount(5, $results);
        $this->assertNotContains('Important', $results->pluck('name')->all());
    }

    public function test_not_equals_operator_with_array_becomes_where_not_in()
    {
        $results = $this->filter(Category::class, ['name:notEquals' => ['Important', 'Urgent']]);

        $this->assertCount(4, $results);
    }

    // ── String matching ──────────────────────────────────────────────────────

    public function test_contains_operator()
    {
        // "Work" and "School" both contain "oo"... let's use "ork" → only Work
        $results = $this->filter(Category::class, ['name:contains' => 'ork']);

        $this->assertCount(1, $results);
        $this->assertEquals('Work', $results->first()->name);
    }

    public function test_starts_with_operator()
    {
        $results = $this->filter(Category::class, ['name:startsWith' => 'P']);

        $this->assertCount(1, $results);
        $this->assertEquals('Personal', $results->first()->name);
    }

    public function test_ends_with_operator()
    {
        // Personal and School both end with 'l'
        $results = $this->filter(Category::class, ['name:endsWith' => 'l']);

        $this->assertCount(2, $results);
        $this->assertContains('Personal', $results->pluck('name')->all());
        $this->assertContains('School', $results->pluck('name')->all());
    }

    public function test_like_operator_supports_raw_wildcards()
    {
        // "%ork" matches anything ending with "ork"
        $results = $this->filter(Category::class, ['name:like' => '%ork']);

        $this->assertCount(1, $results);
        $this->assertEquals('Work', $results->first()->name);
    }

    // ── Numeric comparison ───────────────────────────────────────────────────

    public function test_greater_than_operator()
    {
        // ids > 4 → School(5), Home(6)
        $results = $this->filter(Category::class, ['id:greaterThan' => 4]);

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($r) => $r->id > 4));
    }

    public function test_greater_than_or_equals_operator()
    {
        // ids >= 5 → School(5), Home(6)
        $results = $this->filter(Category::class, ['id:greaterThanOrEquals' => 5]);

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($r) => $r->id >= 5));
    }

    public function test_less_than_operator()
    {
        // ids < 3 → Important(1), Urgent(2)
        $results = $this->filter(Category::class, ['id:lessThan' => 3]);

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($r) => $r->id < 3));
    }

    public function test_less_than_or_equals_operator()
    {
        // ids <= 2 → Important(1), Urgent(2)
        $results = $this->filter(Category::class, ['id:lessThanOrEquals' => 2]);

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($r) => $r->id <= 2));
    }

    public function test_between_operator()
    {
        // ids between 2 and 4 → Urgent(2), Personal(3), Work(4)
        $results = $this->filter(Category::class, ['id:between' => [2, 4]]);

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($r) => $r->id >= 2 && $r->id <= 4));
    }

    public function test_not_between_operator()
    {
        // ids NOT between 2 and 5 → Important(1), Home(6)
        $results = $this->filter(Category::class, ['id:notBetween' => [2, 5]]);

        $this->assertCount(2, $results);
        $ids = $results->pluck('id')->all();
        $this->assertContains(1, $ids);
        $this->assertContains(6, $ids);
    }

    // ── NULL checks ──────────────────────────────────────────────────────────

    public function test_null_operator_filters_null_values()
    {
        // User.email_verified_at is null for the seeded user (not verified)
        $results = $this->filter(User::class, ['email_verified_at:null' => 1]);

        $this->assertTrue($results->every(fn ($u) => $u->email_verified_at === null));
    }

    public function test_not_null_operator_excludes_null_values()
    {
        $verified = User::first();
        $verified->forceFill(['email_verified_at' => now()])->save();

        // The verified user should appear; unverified should not
        $results = $this->filter(User::class, ['email_verified_at:notNull' => 1]);

        $this->assertCount(1, $results);
        $this->assertNotNull($results->first()->email_verified_at);
    }

    // ── Multiple conditions ──────────────────────────────────────────────────

    public function test_multiple_filters_are_combined_with_and_logic()
    {
        // id > 3 AND name ends with 'l' → School(5) and Home(6) end with 'l', but Home doesn't
        // School ends with 'l' → 1 result
        $results = $this->filter(Category::class, [
            'id:greaterThan' => 3,
            'name:endsWith'  => 'l',
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals('School', $results->first()->name);
    }

    // ── Error handling ───────────────────────────────────────────────────────

    public function test_invalid_column_throws_exception_when_throw_enabled()
    {
        $this->expectException(InvalidFilterException::class);

        $this->filter(Category::class, ['nonexistent_column' => 'value']);
    }

    public function test_invalid_column_is_silently_ignored_when_throw_disabled()
    {
        config(['luminix.backend.api.filter.throw' => false]);

        $results = $this->filter(Category::class, ['nonexistent_column' => 'value']);

        // All 6 categories returned (bad filter silently skipped)
        $this->assertCount(6, $results);
    }

    public function test_hidden_columns_are_excluded_from_filtering()
    {
        // User.$hidden includes 'password' — filtering by it must throw
        $this->expectException(InvalidFilterException::class);

        $this->filter(User::class, ['password' => 'secret']);
    }

    public function test_excluded_columns_config_prevents_filtering()
    {
        config(['luminix.backend.api.filter.exclude' => [
            'Workbench\App\Models\Category:name',
        ]]);

        $this->expectException(InvalidFilterException::class);

        $this->filter(Category::class, ['name' => 'Important']);
    }

    // ── Relation operator ────────────────────────────────────────────────────

    public function test_relation_operator_filters_by_related_model_id()
    {
        $user = User::first();
        $todo = $user->toDos()->first(); // seeded todo id=1

        // Attach category id=1 to todo
        $todo->categories()->sync([1]);

        // Filter todos by category id=1 → should return the todo
        $results = $this->filter(ToDo::class, ['categories' => 1]);

        $this->assertCount(1, $results);
        $this->assertEquals($todo->id, $results->first()->id);
    }

    public function test_relation_operator_with_array_filters_by_any_of_given_ids()
    {
        $user = User::first();
        $todo = $user->toDos()->first();

        $todo->categories()->sync([1]);

        // Either category 1 or 2 → todo with category 1 matches
        $results = $this->filter(ToDo::class, ['categories' => [1, 2]]);

        $this->assertCount(1, $results);
    }

    public function test_relation_wildcard_filters_records_with_any_relation()
    {
        $user = User::first();
        $todo = $user->toDos()->first();

        // No categories yet — verify 0 results
        $todo->categories()->detach();
        $this->assertCount(0, $this->filter(ToDo::class, ['categories' => '*']));

        // Attach one category
        $todo->categories()->sync([1]);
        $this->assertCount(1, $this->filter(ToDo::class, ['categories' => '*']));
    }

    // ── Dynamic relations (Model::resolveRelationUsing) ──────────────────────

    public function test_resolve_relation_function_name_recognizes_relations_registered_via_resolve_relation_using()
    {
        // No `dynamicToDos` method exists on Category — only a resolver registered
        // the way a package would declare an inverse relation for a model it doesn't own.
        Category::resolveRelationUsing('dynamicToDos', function (Category $category) {
            return $category->belongsToMany(ToDo::class);
        });

        $filter = new ModelFilter(Category::class, []);

        $resolve = new ReflectionMethod($filter, 'resolveRelationFunctionName');
        $resolve->setAccessible(true);

        $this->assertEquals('dynamicToDos', $resolve->invoke($filter, 'dynamicToDos'));
    }

    public function test_resolve_relation_function_name_still_throws_for_unknown_relation()
    {
        $filter = new ModelFilter(Category::class, []);

        $resolve = new ReflectionMethod($filter, 'resolveRelationFunctionName');
        $resolve->setAccessible(true);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Relation function not found');

        $resolve->invoke($filter, 'nonexistentRelation');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function filter(string $modelClass, array $filters)
    {
        return (new ModelFilter($modelClass, $filters))
            ->apply($modelClass::query())
            ->get();
    }
}
