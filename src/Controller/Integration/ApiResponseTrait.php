<?php

namespace App\Controller\Integration;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shared JSON helpers for all /integration/ controllers.
 */
trait ApiResponseTrait
{
    private function ok(mixed $data): JsonResponse
    {
        return new JsonResponse(['success' => true, 'data' => $data], Response::HTTP_OK);
    }

    private function created(mixed $data): JsonResponse
    {
        return new JsonResponse(['success' => true, 'data' => $data], Response::HTTP_CREATED);
    }

    private function notFound(string $msg = 'Not found'): JsonResponse
    {
        return new JsonResponse(['success' => false, 'message' => $msg], Response::HTTP_NOT_FOUND);
    }

    private function badRequest(string $msg): JsonResponse
    {
        return new JsonResponse(['success' => false, 'message' => $msg], Response::HTTP_BAD_REQUEST);
    }

    private function noContent(): JsonResponse
    {
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /** Decode JSON body; return array or throw 400 */
    private function decodeBody(\Symfony\Component\HttpFoundation\Request $request): array
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Invalid JSON body');
        }
        return $data;
    }
}
