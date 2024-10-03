<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Episode;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EpisodeControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_the_episode_can_be_listed_using_the_api_route(): void
    {
        Episode::factory(11)->create();

        $response = $this->getJson(route('episodes.index'));
        $response->assertStatus(200);

        $this->assertArrayHasKey('episodes', $response->json());
        // Assert pagination size is set to 10
        $this->assertCount(10, $response->json('episodes.data'));
    }

    public function test_the_episode_can_be_shown_using_the_api_route(): void
    {
        $episodeToGet = Episode::factory()->create();

        $response = $this->getJson(route('episodes.show', $episodeToGet->id));
        $response->assertStatus(200);

        $this->assertArrayHasKey('episode', $response->json());
        $this->assertEquals($episodeToGet->id, $response->json('episode.id'));
    }

    public function test_the_episode_can_be_created_using_the_api_route(): void
    {
        $response = $this->postJson(route('episodes.store'), [
            'title' => 'Episode 1',
        ]);

        $response->assertStatus(201);
    }

    public function test_the_episode_can_be_updated_using_the_api_route(): void
    {
        $episodeToUpdate = Episode::factory()->create();

        $response = $this->putJson(route('episodes.update', $episodeToUpdate->id), [
            'title' => 'Episode Updated',
        ]);

        $response->assertStatus(200);

        // assert that the episode has been updated
        $this->assertEquals('Episode Updated', $episodeToUpdate->fresh()->title);
    }

    public function test_the_episode_can_be_deleted_using_the_api_route(): void
    {
        $episodeToDelete = Episode::factory()->create();

        $response = $this->deleteJson(route('episodes.destroy', $episodeToDelete->id));

        $response->assertStatus(200);

        // assert that the episode has been deleted
        $this->assertNull(Episode::find($episodeToDelete->id));
    }
}
