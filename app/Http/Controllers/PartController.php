<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Part;
use App\Services\PartService;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\PartRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PartController extends Controller
{
    /**
     * Constructor to inject PartService dependency.
     *
     * The PartService is injected via the constructor for managing part-related
     * operations that are delegated to the service layer.
     *
     * @param PartService $partService The service responsible for handling part-related logic.
     */
    public function __construct(private readonly PartService $partService)
    {
    }

    /**
     * Display a listing of the parts.
     *
     * This method retrieves and returns a paginated list of parts.
     * The pagination limit is set to 10 parts per page.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'parts' => Part::query()->paginate(10),
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created part in storage.
     *
     * This method handles the creation of a new part by calling the PartService.
     * It supports specifying a position, and if none is provided, it automatically
     * assigns the next available position. The result is returned as JSON.
     *
     * @param PartRequest $request The validated request data for creating the part.
     * @return JsonResponse
     */
    public function store(PartRequest $request): JsonResponse
    {
        try {
            $part = $this->partService->createPart($request->validated());

            return response()->json([
                'part' => $part,
                'message' => 'Episode part created successfully',
            ], Response::HTTP_CREATED);

        } catch (Exception $exception) {
            Log::error($exception);

            return response()->json([
                'message' => "Episode part couldn't be created",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified part.
     *
     * This method returns the details of a single part that is passed as an argument.
     * The part is automatically injected via route model binding.
     *
     * @param Part $part The part instance provided by route model binding.
     * @return JsonResponse
     */
    public function show(Part $part): JsonResponse
    {
        return response()->json([
            'part' => $part,
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified part in storage.
     *
     * This method updates an existing part by delegating the logic to PartService.
     * It handles updates to the part's title and position, ensuring that the
     * part is not updated concurrently using database locks. The innodb_lock_wait_timeout
     * is temporarily set to handle potential lock waits.
     *
     * @param PartRequest $request The validated request data for updating the part.
     * @param Part $part The part instance to update, provided by route model binding.
     * @return JsonResponse
     */
    public function update(PartRequest $request, Part $part): JsonResponse
    {
        try {
            $this->partService->updatePart($part, $request->validated());

            return response()->json([
                'message' => 'Episode Part updated successfully',
            ], Response::HTTP_OK);

        } catch (Exception $exception) {
            if (str_contains($exception->getMessage(), 'Deadlock found')) {
                return response()->json([
                    'message' => "Episode part update is temporarily unavailable. Please try again.",
                ], Response::HTTP_SERVICE_UNAVAILABLE);
            }

            Log::error($exception);
            return response()->json([
                'message' => "Episode part couldn't be updated",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Sort parts from specified resource.
     *
     * This method allows updating the part's position using PartService.
     * It ensures that the operation is transactional, meaning if any errors
     * occur, all changes are rolled back. A job is dispatched to reorder the
     * parts after the position is updated.
     *
     * @param PartRequest $request The validated request data for sorting the part.
     * @param Part $part The part instance to update, provided by route model binding.
     * @return JsonResponse
     */
    public function sort(PartRequest $request, Part $part): JsonResponse
    {
        return $this->update($request, $part);
    }

    /**
     * Remove the specified part from storage.
     *
     * This method deletes an existing part by performing the operation inside
     * a transaction, ensuring atomicity. After deletion, a background job can
     * be dispatched to reorder the remaining parts.
     *
     * @param Part $part The part instance to delete, provided by route model binding.
     * @return JsonResponse
     */
    public function destroy(Part $part): JsonResponse
    {
        try {
            DB::transaction(function () use ($part) {
                $part->delete();
            }, 3);

            // Dispatch a job to reorder the parts after the part is deleted
            // SortEpisodeParts::dispatch(part: $part, position: $part->position, method: 'decrement');

            return response()->json([
                'message' => 'Episode part deleted successfully',
            ], Response::HTTP_OK);

        } catch (Exception $exception) {
            Log::error($exception);

            return response()->json([
                'message' => "Episode part couldn't be deleted successfully",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
