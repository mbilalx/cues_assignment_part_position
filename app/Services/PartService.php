<?php

namespace App\Services;

use Exception;
use App\Models\Part;
use App\Models\Episode;
use App\Jobs\SortEpisodeParts;
use App\Helpers\DatabaseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PartService
{
    /**
     * Get the next available position for a part in a given episode.
     *
     * This method calculates the next available position for a new part within
     * the specified episode by getting the maximum position of existing parts
     * and incrementing it by 1. If no parts exist, the returned position will be 1.
     *
     * @param int $episode_id The episode_id to get the next part position for.
     * @return int The next available position.
     */
    public function getNextAvailablePosition(int $episode_id): int
    {
        return Episode::find($episode_id)?->parts()->max('position') + 1;
    }

    /**
     * Create a new part in the database.
     *
     * This method handles the creation of a new part. If a position is not provided,
     * it automatically assigns the next available position. The creation process
     * is wrapped in a transaction to ensure data consistency and atomicity.
     *
     * Additionally, if the provided position does not match the next available
     * position, a background job is dispatched to reorder the parts.
     *
     * @param array $properties The properties for creating the part (validated request data).
     * @return Part The newly created part.
     * @throws Exception If the creation fails, the exception is logged and rethrown.
     */
    public function createPart(array $properties): Part
    {
        try {
            $availablePosition = $this->getNextAvailablePosition($properties['episode_id']);
            $properties['position'] = $properties['position'] ?? $availablePosition;
            $part = DB::transaction(function () use ($properties) {
                return Part::create($properties);
            }, 3); // Retry the transaction 3 times if it fails due to deadlock issues

            // If a specific position was provided, and it's not the last position, dispatch a job to reorder parts
            if (intval($properties['position']) !== $availablePosition) {
                $this->dispatchJobToSortPartsAfterPosition($part, position: $properties['position']);
            }

            return $part;
        } catch (Exception $exception) {
            Log::error($exception);
            throw $exception;
        }
    }

    /**
     * Update an existing part in the database.
     *
     * This method updates the properties of an existing part, ensuring data consistency
     * by locking the part record during the update. It temporarily changes the
     * innodb_lock_wait_timeout to 5 seconds to avoid long lock waits. After the update,
     * if the position has been modified, a job is dispatched to reorder the parts.
     *
     * @param Part $part The part to update.
     * @param array $propertiesToUpdate The properties to update (validated request data).
     * @return Part The updated part instance.
     * @throws Exception If the update fails, the exception is rethrown after the lock timeout is reset.
     */
    public function updatePart(Part $part, array $propertiesToUpdate): Part
    {
        $currentLockTimeout = 50; // Default lock timeout
        try {
            // Get and temporarily set a shorter lock timeout
            $currentLockTimeout = DatabaseHelper::getCurrentLockTimeout();
            DatabaseHelper::setLockTimeout(5);

            // Perform the update in a transaction
            DB::beginTransaction();

            $part = Part::lockForUpdate()->find($part->id); // Lock part for update
            $part->update($propertiesToUpdate); // Update the part properties

            DB::commit();

            // Reset the lock timeout back to the original value
            DatabaseHelper::setLockTimeout($currentLockTimeout);

            // Dispatch a job to reorder the parts if the position has changed
            if (array_key_exists('position', $propertiesToUpdate)) {
                $this->dispatchJobToSortPartsAfterPosition($part, $part->position);
            }

            return $part;

        } catch (Exception $exception) {
            DatabaseHelper::setLockTimeout($currentLockTimeout);
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * Dispatch a job to reorder parts after the part's position is updated.
     *
     * This helper method is responsible for dispatching the SortEpisodeParts job
     * to handle the reordering of parts when a part's position is updated.
     *
     * @param Part $part The part that has been repositioned.
     * @param int $position The new position for the part.
     * @return void
     */
    private function dispatchJobToSortPartsAfterPosition(Part $part, int $position): void
    {
        SortEpisodeParts::dispatch(part: $part, position: $position);
    }
}
