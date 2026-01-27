<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\TestCase;

class CategoryModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a category can be created with required attributes.
     */
    public function test_category_can_be_created(): void
    {
        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'A test category description',
        ]);

        $this->assertNotNull($category->id);
        $this->assertEquals('Test Category', $category->name);
        $this->assertEquals('test-category', $category->slug);
        $this->assertEquals('A test category description', $category->description);
    }

    /**
     * Test category slug is automatically generated from name.
     */
    public function test_category_slug_is_generated_from_name(): void
    {
        $category = Category::create([
            'name' => 'Test Category Name',
            'description' => 'Description',
        ]);

        $this->assertEquals('test-category-name', $category->slug);
    }

    /**
     * Test category has many videos relationship.
     */
    public function test_category_has_many_videos(): void
    {
        $category = Category::factory()->create();
        $video1 = Video::factory()->create(['category_id' => $category->id]);
        $video2 = Video::factory()->create(['category_id' => $category->id]);

        $this->assertCount(2, $category->videos);
        $this->assertTrue($category->videos->contains($video1));
        $this->assertTrue($category->videos->contains($video2));
    }

    /**
     * Test category slug must be unique.
     */
    public function test_category_slug_must_be_unique(): void
    {
        Category::create([
            'name' => 'Category 1',
            'slug' => 'unique-slug',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Category::create([
            'name' => 'Category 2',
            'slug' => 'unique-slug',
        ]);
    }

    /**
     * Test category name is required.
     */
    public function test_category_name_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Category::create([
            'slug' => 'no-name',
        ]);
    }

    /**
     * Test category can have null description.
     */
    public function test_category_can_have_null_description(): void
    {
        $category = Category::create([
            'name' => 'No Description Category',
            'slug' => 'no-desc',
        ]);

        $this->assertNull($category->description);
    }

    /**
     * Test category active videos scope.
     */
    public function test_category_has_active_videos_scope(): void
    {
        $category = Category::factory()->create();
        $activeVideo = Video::factory()->create(['category_id' => $category->id, 'status' => 'active']);
        $inactiveVideo = Video::factory()->create(['category_id' => $category->id, 'status' => 'inactive']);

        $activeVideos = $category->activeVideos()->get();

        $this->assertCount(1, $activeVideos);
        $this->assertEquals($activeVideo->id, $activeVideos->first()->id);
    }

    /**
     * Test category fillable attributes.
     */
    public function test_category_fillable_attributes(): void
    {
        $category = new Category();
        $fillable = $category->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('slug', $fillable);
        $this->assertContains('description', $fillable);
    }
}

