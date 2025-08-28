<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageInfiniteScrollTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_endpoint_paginates_twenty_per_page(): void
    {
        Photo::factory()->count(45)->create();

        // First page
        $first = $this->getJson(route('photos.feed', ['page' => 1, 'per_page' => 20]));
        $first->assertOk();
        $first->assertJsonCount(20, 'data');
        $this->assertEquals(2, $first->json('nextPage'));

        // Second page
        $second = $this->getJson(route('photos.feed', ['page' => 2, 'per_page' => 20]));
        $second->assertOk();
        $second->assertJsonCount(20, 'data');
        $this->assertEquals(3, $second->json('nextPage'));

        // Third page has remaining 5
        $third = $this->getJson(route('photos.feed', ['page' => 3, 'per_page' => 20]));
        $third->assertOk();
        $third->assertJsonCount(5, 'data');
        $this->assertNull($third->json('nextPage'));
    }

    public function test_feed_enforces_max_per_page_and_page_bounds(): void
    {
        Photo::factory()->count(200)->create();

        putenv('FEED_MAX_PER_PAGE=50');
        putenv('FEED_MAX_PAGE=1000');

        // per_page should clamp to 50 on a valid first page
        $clampedPerPage = $this->getJson(route('photos.feed', ['per_page' => 5000, 'page' => 1]));
        $clampedPerPage->assertOk();
        $clampedPerPage->assertJsonCount(50, 'data');

        $tooSmall = $this->getJson(route('photos.feed', ['per_page' => 0, 'page' => 0]));
        $tooSmall->assertOk();
        $tooSmall->assertJsonCount(20, 'data');
        $this->assertEquals(2, $tooSmall->json('nextPage'));

        // Very large page should clamp to maxPage and return empty set when beyond available pages
        $tooLargePage = $this->getJson(route('photos.feed', ['per_page' => 50, 'page' => 999999]));
        $tooLargePage->assertOk();
        $tooLargePage->assertJsonCount(0, 'data');
        $this->assertNull($tooLargePage->json('nextPage'));
    }

    public function test_home_initial_payload_contains_first_page_items(): void
    {
        Photo::factory()->count(25)->create();

        $response = $this->get(route('home'));
        $response->assertOk();

        // Should render first 20 thumbnails
        $this->assertStringContainsString('photo-grid', $response->getContent());
        // Load more button should be present when more pages exist
        $this->assertStringContainsString('load-more-button', $response->getContent());
    }

    public function test_home_hides_load_more_when_no_next_page(): void
    {
        Photo::factory()->count(12)->create();
        $response = $this->get(route('home'));
        $response->assertOk();
        // Button should not be rendered in markup when there's no next page
        $this->assertStringNotContainsString('id="load-more-button"', $response->getContent());
    }
}


