<?php

namespace Workbench\App\Tests\Feature;

use Workbench\App\Models\User;
use Workbench\App\Tests\TestCase;

/**
 * Integration tests for the filtering/search/ordering API parameters.
 *
 * Seeded categories (ids 1-6): Important, Urgent, Personal, Work, School, Home
 */
class FilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::first());
    }

    // ── `where` filter ───────────────────────────────────────────────────────

    public function test_where_equals_returns_matching_records()
    {
        $response = $this->json('GET', '/luminix-api/categories', ['where' => ['name' => 'Important']]);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Important', $response->json('data.0.name'));
    }

    public function test_where_not_equals_excludes_value()
    {
        $response = $this->json('GET', '/luminix-api/categories', ['where' => ['name:notEquals' => 'Important']]);

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }

    public function test_where_contains_filters_substring()
    {
        $response = $this->json('GET', '/luminix-api/categories', ['where' => ['name:contains' => 'ork']]);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Work', $response->json('data.0.name'));
    }

    public function test_where_starts_with_filters_prefix()
    {
        $response = $this->json('GET', '/luminix-api/categories', ['where' => ['name:startsWith' => 'P']]);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Personal', $response->json('data.0.name'));
    }

    public function test_where_ends_with_filters_suffix()
    {
        $response = $this->json('GET', '/luminix-api/categories', ['where' => ['name:endsWith' => 'l']]);

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_where_greater_than_filters_numerically()
    {
        $response = $this->json('GET', '/luminix-api/categories', ['where' => ['id:greaterThan' => 4]]);

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        foreach ($response->json('data') as $item) {
            $this->assertGreaterThan(4, $item['id']);
        }
    }

    public function test_where_less_than_filters_numerically()
    {
        $response = $this->json('GET', '/luminix-api/categories', ['where' => ['id:lessThan' => 3]]);

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        foreach ($response->json('data') as $item) {
            $this->assertLessThan(3, $item['id']);
        }
    }

    public function test_where_between_filters_inclusive_range()
    {
        $response = $this->json('GET', '/luminix-api/categories', ['where' => ['id:between' => [2, 4]]]);

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
        foreach ($response->json('data') as $item) {
            $this->assertGreaterThanOrEqual(2, $item['id']);
            $this->assertLessThanOrEqual(4, $item['id']);
        }
    }

    public function test_where_not_between_filters_outside_range()
    {
        $response = $this->json('GET', '/luminix-api/categories', ['where' => ['id:notBetween' => [2, 5]]]);

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        $ids = array_column($response->json('data'), 'id');
        $this->assertContains(1, $ids);
        $this->assertContains(6, $ids);
    }

    public function test_where_multiple_conditions_use_and_logic()
    {
        // id > 3 AND name ends with 'l' → only School(5) qualifies
        $response = $this->json('GET', '/luminix-api/categories', [
            'where' => [
                'id:greaterThan' => 3,
                'name:endsWith'  => 'l',
            ],
        ]);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('School', $response->json('data.0.name'));
    }

    // ── `q` search ──────────────────────────────────────────────────────────

    public function test_search_q_returns_matching_records()
    {
        $response = $this->json('GET', '/luminix-api/categories?q=ork');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Work', $response->json('data.0.name'));
    }

    public function test_search_q_is_case_insensitive()
    {
        $response = $this->json('GET', '/luminix-api/categories?q=work');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_search_q_returns_empty_when_no_match()
    {
        $response = $this->json('GET', '/luminix-api/categories?q=zzznomatch');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    // ── `order_by` ───────────────────────────────────────────────────────────

    public function test_order_by_ascending_sorts_correctly()
    {
        $response = $this->json('GET', '/luminix-api/categories?order_by=name:asc');

        $response->assertStatus(200);
        $names = array_column($response->json('data'), 'name');
        $sorted = $names;
        sort($sorted);
        $this->assertEquals($sorted, $names);
    }

    public function test_order_by_descending_sorts_correctly()
    {
        $response = $this->json('GET', '/luminix-api/categories?order_by=name:desc');

        $response->assertStatus(200);
        $names = array_column($response->json('data'), 'name');
        $sorted = $names;
        rsort($sorted);
        $this->assertEquals($sorted, $names);
    }

    // ── Pagination ───────────────────────────────────────────────────────────

    public function test_per_page_limits_results()
    {
        $response = $this->json('GET', '/luminix-api/categories?per_page=2&page=1');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        $this->assertEquals(6, $response->json('meta.total'));
        $this->assertEquals(3, $response->json('meta.last_page'));
    }

    public function test_page_parameter_returns_correct_page()
    {
        $first  = $this->json('GET', '/luminix-api/categories?per_page=2&page=1')->json('data');
        $second = $this->json('GET', '/luminix-api/categories?per_page=2&page=2')->json('data');

        $this->assertNotEquals(
            array_column($first, 'id'),
            array_column($second, 'id')
        );
    }

    // ── `tab` filtering ──────────────────────────────────────────────────────

    public function test_tab_parameter_is_applied()
    {
        // Default WhereBelongsToTab falls through to `default => $query` (no-op for non-trashed tab)
        $response = $this->json('GET', '/luminix-api/categories?tab=all');

        $response->assertStatus(200);
        $this->assertCount(6, $response->json('data'));
    }
}
