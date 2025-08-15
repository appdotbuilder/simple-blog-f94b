<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;

test('blog index displays published posts', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertInertia(function ($assert) {
        $assert->component('welcome')
            ->has('posts.data')
            ->has('categories')
            ->has('tags')
            ->has('filters');
    });
});

test('can filter posts by category', function () {
    // Create test data
    $user = User::factory()->create();
    $category = Category::factory()->create(['slug' => 'test-category']);
    Post::factory(2)->published()->for($user, 'author')->for($category)->create();
    
    $response = $this->get('/?category=test-category');

    $response->assertStatus(200);
    $response->assertInertia(function ($assert) {
        $assert->component('welcome')
            ->has('posts.data')
            ->where('filters.category', 'test-category');
    });
});

test('can view individual blog post', function () {
    // Create test data
    $user = User::factory()->create();
    $category = Category::factory()->create();
    $post = Post::factory()->published()
        ->for($user, 'author')
        ->for($category)
        ->create(['slug' => 'test-post-slug']);
    
    $initialViews = $post->views_count;

    $response = $this->get("/posts/{$post->slug}");

    $response->assertStatus(200);
    $response->assertInertia(function ($assert) use ($post) {
        $assert->component('blog/show')
            ->where('post.title', $post->title)
            ->where('post.content', $post->content)
            ->has('post.author')
            ->has('relatedPosts');
    });

    // Check that views count was incremented
    expect($post->fresh()->views_count)->toBe($initialViews + 1);
});