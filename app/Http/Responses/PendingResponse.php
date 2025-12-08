<?php

namespace App\Http\Responses;

use App\Models\Character;
use App\Models\Guild;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

class PendingResponse implements Responsable
{
    public function __construct(
        public Character|Guild $entity,
        public ?int $estimatedWait = 30,
        public ?string $message = null
    ) {
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toResponse($request): JsonResponse
    {
        $entityType = $this->entity instanceof Character ? 'character' : 'guild';

        return response()->json([
            'status' => 'pending',
            'message' => $this->message ?? 'High demand - your request is queued',
            'estimated_wait_seconds' => $this->estimatedWait,
            'poll_url' => route("{$entityType}.status", ['id' => $this->entity->id]),
            $entityType => [
                'id' => $this->entity->id,
                'region' => $this->entity->region,
                'realm' => $this->entity->realm,
                'name' => $this->entity->name,
            ],
        ], 202);
    }
}
