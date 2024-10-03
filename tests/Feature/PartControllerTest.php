<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Part;
use App\Models\Episode;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PartControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test that the episode parts can be listed via the API route.
     *
     * This test ensures that parts for an episode are correctly returned
     * in a paginated format. It verifies that the parts are retrieved
     * successfully and that pagination is limited to 10 parts per page.
     */
    public function test_the_episode_part_can_be_listed_using_the_api_route(): void
    {
        $episode = Episode::factory()->create();
        Part::factory(11)->create([
            'episode_id' => $episode->id,
        ]);

        $response = $this->getJson(route('episodes.parts.index'));

        $response->assertStatus(200);
        $this->assertArrayHasKey('parts', $response->json());
        $this->assertCount(10, $response->json('parts.data'));
    }

    /**
     * Test that an individual episode part can be shown via the API route.
     *
     * This test ensures that a specific part can be retrieved by its ID.
     * The test checks that the correct part data is returned, and that the
     * response contains the expected part information.
     */
    public function test_the_episode_part_can_be_shown_using_the_api_route(): void
    {
        $episode = Episode::factory()->create();
        $partToGet = Part::factory()->create([
            'episode_id' => $episode->id,
        ]);

        $response = $this->getJson(route('episodes.parts.show', $partToGet->id));

        $response->assertStatus(200);
        $this->assertArrayHasKey('part', $response->json());
        $this->assertEquals($partToGet->id, $response->json('part.id'));
    }

    /**
     * Test that an episode part can be created via the API route.
     *
     * This test ensures that a part can be created successfully.
     * The response is checked to ensure that the part is created,
     * and the status code is 201 Created.
     */
    public function test_the_episode_part_can_be_created_using_the_api_route(): void
    {
        $episode = Episode::factory()->create();
        $response = $this->postJson(route('episodes.parts.store'), [
            'episode_id' => $episode->id,
            'title' => 'Part 1',
            'position' => 1
        ]);

        $response->assertStatus(201);
    }

    /**
     * Test that an episode part can be updated via the API route.
     *
     * This test ensures that an existing part can be updated,
     * including its title and position. The test checks that
     * the part is updated in the database after the request.
     */
    public function test_the_episode_part_can_be_updated_using_the_api_route(): void
    {
        $episode = Episode::factory()->create();

        // Create 5 parts for the episode
        $existingParts = [];
        for ($i = 1; $i <= 5; $i++) {
            $existingParts[] = Part::factory()->create([
                'episode_id' => $episode->id,
                'position' => $i, // Set position from 1 to 5
            ]);
        }

        // Randomly select one part to update
        $partToUpdate = $existingParts[$this->faker->numberBetween(0, 4)];

        $response = $this->putJson(route('episodes.parts.update', $partToUpdate->id), [
            'title' => 'Part Updated',
            'position' => 9
        ]);
        $response->assertStatus(200);
        $updatedPart = $partToUpdate->fresh(); // Reload the part from the database

        $this->assertEquals('Part Updated', $updatedPart->title);
        $this->assertEquals(9, $updatedPart->position);
    }

    /**
     * Test that an episode part can be deleted via the API route.
     *
     * This test ensures that a part can be deleted successfully.
     * The test checks that the part no longer exists in the database
     * after the deletion request.
     */
    public function test_the_episode_part_can_be_deleted_using_the_api_route(): void
    {
        $episode = Episode::factory()->create();

        // Create 5 parts for the episode
        $existingParts = [];
        for ($i = 1; $i <= 5; $i++) {
            $existingParts[] = Part::factory()->create([
                'episode_id' => $episode->id,
                'title' => "Part $i",
                'position' => $i,
            ]);
        }

        // Randomly select one part to delete
        $partToDelete = $existingParts[$this->faker->numberBetween(0, 4)];
        $response = $this->deleteJson(route('episodes.parts.destroy', $partToDelete->id));

        $response->assertStatus(200);
        $this->assertNull(Part::find($partToDelete->id));
    }

    /**
     * Test that the episode part can be sorted via the API route.
     *
     * This test ensures that the part's position can be updated (re-sorted)
     * and that the other parts are reordered accordingly after the position update.
     * The test verifies the new position and ensures the sorting logic works as expected.
     */
    public function test_the_episode_part_can_be_sorted_using_the_api_route(): void
    {
        $episode = Episode::factory()->create();

        // Create 50 parts for the episode, each with a unique position
        $existingParts = [];
        for ($i = 1; $i <= 50; $i++) {
            $existingParts[] = Part::factory()->create([
                'episode_id' => $episode->id,
                'title' => "Part $i",
                'position' => $i,
            ]);
        }

        // Randomly select one part to reposition
        $index = $this->faker->numberBetween(0, 49);
        $partToReposition = $existingParts[$index];

        // Set a new random position for the part
        $newPosition = $this->faker->numberBetween(0, 49);

        $response = $this->patchJson(route('episodes.parts.sort', $partToReposition->id), [
            'position' => $newPosition
        ]);

        $response->assertStatus(200);
        $repositionedPart = $partToReposition->fresh(); // Reload the part from the database
        $this->assertEquals($newPosition, $repositionedPart->position);

        // Validate the sorting logic by checking the position of other parts
        for ($i = Part::where('position', $newPosition + 1)->first()->id ?? 50; $i < 50; $i++) {
            $newPosition++;
            if ($repositionedPart->id == $i) {
                continue;
            }
            $this->assertEquals(Part::find($i)->position, $newPosition);
        }
    }
}
