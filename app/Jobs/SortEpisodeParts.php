<?php

namespace App\Jobs;

use App\Models\Part;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class SortEpisodeParts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * The constructor initializes the properties for sorting parts.
     * It accepts a Part instance, the target position to move the part to,
     * and a method to determine whether to increment or decrement other parts' positions.
     *
     * @param Part $part The part being repositioned.
     * @param int $position The new position for the part.
     * @param string $method Whether to 'increment' or 'decrement' part positions.
     */
    public function __construct(private readonly Part $part, private int $position, private readonly string $method = 'increment')
    {
    }

    /**
     * Middleware to prevent job overlapping.
     *
     * The WithoutOverlapping middleware ensures that jobs for the same episode
     * don't run simultaneously, which helps avoid race conditions when updating
     * part positions.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        // Ensure that only one job for the same episode is processed at a time
        return [new WithoutOverlapping($this->part->episode_id)];
    }

    /**
     * Execute the job.
     *
     * The handle method executes the logic for repositioning parts within the episode.
     * It runs a transaction to lock the parts that need to be repositioned and either
     * increments or decrements their positions based on the $method property.
     */
    public function handle(): void
    {
        try {
            DB::transaction(function () {
                $baseQuery = Part::where('episode_id', $this->part->episode_id)
                    ->where('position', '>=', $this->position);

                // Lock rows to prevent concurrent modifications
                $baseQuery->lockForUpdate()->get();

                // Increment or decrement the positions of the affected parts
                $baseQuery->{$this->method}('position', 1);

                $this->part->refresh();
                // Update the target part's position to the new value
                $this->part->update(['position' => $this->position]);
            });
        } catch (\Exception $exception) {
            Log::debug($exception);
        }
    }
}
