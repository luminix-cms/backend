<?php

namespace Workbench\App\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Luminix\Backend\Facades\Finder;
use Luminix\Backend\Model\LuminixModel;
use Workbench\App\Models\Category;
use Workbench\App\Models\ToDo;
use Workbench\App\Models\User;
use Workbench\App\Tests\TestCase;

class ModelFinderTest extends TestCase
{
    public function test_it_can_find_models()
    {
        $models = Finder::all();

        $this->assertEquals([
            'user'     => 'Workbench\App\Models\User',
            'to_do'    => 'Workbench\App\Models\ToDo',
            'category' => 'Workbench\App\Models\Category',
            'tag'      => 'Workbench\App\Models\Tag',
        ], $models->toArray());
    }

    public function test_it_reads_the_alias_and_action_from_a_route_name()
    {
        $this->assertEquals('to_do', Finder::aliasFromRouteName('luminix.to_do.index'));
        $this->assertEquals('index', Finder::actionFromRouteName('luminix.to_do.index'));
    }

    public function test_it_keeps_the_relation_action_whole()
    {
        // Relation routes are named `{relation}:{action}`; splitting on the dot
        // must not cut them in half.
        $this->assertEquals('category', Finder::aliasFromRouteName('luminix.category.tags:sync'));
        $this->assertEquals('tags:sync', Finder::actionFromRouteName('luminix.category.tags:sync'));
    }

    public function test_a_foreign_route_name_reads_as_nothing()
    {
        // Applications ask about every route they see, not only ours. Returning
        // null is what lets them tell "not a Luminix endpoint" from "an endpoint
        // whose alias I could not resolve".
        $this->assertNull(Finder::aliasFromRouteName('admin.dashboard.index'));
        $this->assertNull(Finder::aliasFromRouteName('luminix.to_do'));
        $this->assertNull(Finder::aliasFromRouteName(null));
    }

    public function test_to_alias_converts_class_to_alias()
    {
        $this->assertEquals('user',     Finder::toAlias(User::class));
        $this->assertEquals('to_do',    Finder::toAlias(ToDo::class));
        $this->assertEquals('category', Finder::toAlias(Category::class));
    }

    public function test_to_class_converts_alias_to_class()
    {
        $this->assertEquals(User::class,     Finder::toClass('user'));
        $this->assertEquals(ToDo::class,     Finder::toClass('to_do'));
        $this->assertEquals(Category::class, Finder::toClass('category'));
    }

    public function test_is_luminix_model_returns_true_for_models_using_trait()
    {
        $this->assertTrue(Finder::isLuminixModel(User::class));
        $this->assertTrue(Finder::isLuminixModel(ToDo::class));
        $this->assertTrue(Finder::isLuminixModel(Category::class));
    }

    public function test_is_luminix_model_returns_false_for_base_eloquent_model()
    {
        // Base Model is abstract and does not use LuminixModel
        $this->assertFalse(Finder::isLuminixModel(Model::class));
    }

    public function test_is_luminix_model_returns_false_for_non_luminix_model()
    {
        // Anonymous concrete Eloquent model without the trait
        $plain = new class extends Model {};
        $this->assertFalse(Finder::isLuminixModel($plain));
    }

    public function test_class_uses_detects_trait_recursively()
    {
        $this->assertTrue(Finder::classUses(User::class, LuminixModel::class));
    }

    public function test_class_uses_returns_false_when_trait_is_absent()
    {
        $this->assertFalse(Finder::classUses(User::class, \Illuminate\Database\Eloquent\SoftDeletes::class));
    }
}
