<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test can list all categories (public endpoint).
     */
    public function test_can_list_all_categories(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test can show single category (public endpoint).
     */
    public function test_can_show_single_category(): void
    {
        $category = Category::factory()->create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $response = $this->getJson('/api/categories/' . $category->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Category retrieved successfully',
                'data' => [
                    'id' => $category->id,
                    'name' => 'Test Category',
                    'slug' => 'test-category',
                ],
            ]);
    }

    /**
     * Test returns 404 for non-existent category.
     */
    public function test_returns_404_for_non_existent_category(): void
    {
        $response = $this->getJson('/api/categories/999');

        $response->assertStatus(404);
    }

    /**
     * Test authenticated user can create category.
     */
    public function test_authenticated_user_can_create_category(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/categories', [
            'name' => 'New Category',
            'description' => 'Category description',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'message' => 'Category created successfully',
                'data' => [
                    'name' => 'New Category',
                    'slug' => 'new-category',
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'New Category',
            'slug' => 'new-category',
        ]);
    }

    /**
     * Test creating category validates required name.
     */
    public function test_create_category_validates_name_required(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/categories', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test creating category validates unique name.
     */
    public function test_create_category_validates_unique_name(): void
    {
        Category::factory()->create(['name' => 'Existing Category']);

        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/categories', [
            'name' => 'Existing Category',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test unauthenticated user cannot create category.
     */
    public function test_unauthenticated_user_cannot_create_category(): void
    {
        $response = $this->postJson('/api/categories', [
            'name' => 'New Category',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test authenticated user can update category.
     */
    public function test_authenticated_user_can_update_category(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $category = Category::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/categories/' . $category->id, [
            'name' => 'Updated Category',
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Category updated successfully',
                'data' => [
                    'name' => 'Updated Category',
                    'description' => 'Updated description',
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Category',
        ]);
    }

    /**
     * Test authenticated user can delete category.
     */
    public function test_authenticated_user_can_delete_category(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $category = Category::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/categories/' . $category->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Category deleted successfully',
            ]);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    /**
     * Test unauthenticated user cannot update category.
     */
    public function test_unauthenticated_user_cannot_update_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->putJson('/api/categories/' . $category->id, [
            'name' => 'Updated Category',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test unauthenticated user cannot delete category.
     */
    public function test_unauthenticated_user_cannot_delete_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->deleteJson('/api/categories/' . $category->id);

        $response->assertStatus(401);
    }
}

