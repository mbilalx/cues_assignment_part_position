<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Part;
use App\Models\Episode;
use App\Jobs\SortEpisodeParts;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SortEpisodePartsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_sort_episode_parts_job_is_pushed_to_queue(): void
    {
        Queue::fake();

        $episode = Episode::factory()->create();
        $part = Part::factory()->create(['episode_id' => $episode->id, 'position' => 1]);

        SortEpisodeParts::dispatch($part, 2, 'increment');

        Queue::assertPushed(SortEpisodeParts::class);
    }

    public function test_sort_episode_parts_job_increments_positions(): void
    {
        Queue::fake();

        $episode = Episode::factory()->create();
        $existingParts = [];
        for ($i = 1; $i <= 50; $i++) {
            $existingParts[] = Part::factory()->create([
                'episode_id' => $episode->id,
                'position' => $i, // Set position from 1 to 50
            ]);
        }
        $targetPart = $existingParts[$this->faker->numberBetween(0, 49)];

        // Set a new random position for the part
        $newPosition = $this->faker->numberBetween(0, 49);

        // Dispatch the job to move the target part to new position
        SortEpisodeParts::dispatch($targetPart, $newPosition, 'increment');

        // Execute the job
        Queue::assertPushed(SortEpisodeParts::class, function ($job) use ($newPosition, $targetPart) {
            $job->handle();

            $repositionedPart = $targetPart->fresh(); // Reload the part from the database
            $this->assertEquals($newPosition, $repositionedPart->position);
            for ($i = Part::where('position', $newPosition + 1)->first()->id ?? 50; $i < 50; $i++) {
                $newPosition++;
                if ($repositionedPart->id == $i) {
                    continue;
                }
                $this->assertEquals(Part::find($i)->position, $newPosition);
            }
            return true;
        });
    }

    public function test_sort_episode_parts_job_no_parts_modified_when_no_repositioning_needed(): void
    {
        // Fake the queue
        Queue::fake();

        // Create an episode with a single part
        $episode = Episode::factory()->create();
        $part = Part::factory()->create(['episode_id' => $episode->id, 'position' => 1]);

        // Dispatch the job but set the new position to the same as the current
        SortEpisodeParts::dispatch($part, 1, 'increment');

        // Execute the job
        Queue::assertPushed(SortEpisodeParts::class, function ($job) use ($part) {
            $job->handle();

            // Assert that the part's position was not modified
            $this->assertEquals(1, Part::find($part->id)->position); // No change

            return true;
        });
    }
}
