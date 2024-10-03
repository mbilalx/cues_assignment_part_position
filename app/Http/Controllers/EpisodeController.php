<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Episode;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\EpisodeRequest;
use Symfony\Component\HttpFoundation\Response;

class EpisodeController extends Controller
{
    /**
     * Display a listing of the episodes.
     *
     * This method retrieves and returns a paginated list of episodes.
     * The pagination limit is set to 10 episodes per page.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'episodes' => Episode::query()->paginate(10),
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created episode in storage.
     *
     * This method handles the creation of a new episode. The request is validated
     * via EpisodeRequest, and the creation happens inside a transaction to ensure
     * database consistency. If the episode creation fails, a server error response is returned.
     *
     * @param EpisodeRequest $request The validated request data for creating the episode.
     * @return JsonResponse
     */
    public function store(EpisodeRequest $request): JsonResponse
    {
        try {
            $episode = Episode::query()->create([
                'title' => $request->title,
            ]);
            return response()->json([
                'episode' => $episode,
                'message' => 'Episode created successfully',
            ], Response::HTTP_CREATED);
        } catch (Exception $exception) {
            Log::error($exception);

            return response()->json([
                'message' => "Episode couldn't created successfully",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified episode.
     *
     * This method returns the details of a single episode that is passed as an argument.
     * The episode is automatically injected via route model binding.
     *
     * @param Episode $episode The episode instance provided by route model binding.
     * @return JsonResponse
     */
    public function show(Episode $episode): JsonResponse
    {
        return response()->json([
            'episode' => $episode,
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified episode in storage.
     *
     * This method updates an existing episode using data from the request.
     * The update happens inside a transaction to ensure atomicity.
     * The updated title is taken from the validated request.
     *
     * @param EpisodeRequest $request The validated request data for updating the episode.
     * @param Episode $episode The episode instance to update, provided by route model binding.
     * @return JsonResponse
     */
    public function update(EpisodeRequest $request, Episode $episode): JsonResponse
    {
        try {
            $episode->update([
                'title' => $request->title,
            ]);
            return response()->json([
                'message' => 'Episode updated successfully',
            ], Response::HTTP_OK);
        } catch (Exception $exception) {
            Log::error($exception);

            return response()->json([
                'message' => "Episode couldn't updated",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified episode from storage.
     *
     * This method deletes an existing episode. The deletion is performed
     * inside a transaction to ensure that if any errors occur, the operation
     * is rolled back.
     *
     * @param Episode $episode The episode instance to delete, provided by route model binding.
     * @return JsonResponse
     */
    public function destroy(Episode $episode): JsonResponse
    {
        try {
            $episode->delete();
            return response()->json([
                'message' => 'Episode deleted successfully',
            ], Response::HTTP_OK);
        } catch (Exception $exception) {
            Log::error($exception);

            return response()->json([
                'message' => "Episode couldn't deleted",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
