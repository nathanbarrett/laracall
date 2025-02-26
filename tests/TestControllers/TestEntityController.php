<?php

namespace NathanBarrett\LaraCall\Tests\TestControllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TestEntityController
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'index']);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'store',
            'body' => $request->all(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['message' => 'show', 'id' => $id]);
    }

    public function update(int $id): JsonResponse
    {
        return response()->json(['message' => 'update', 'id' => $id]);
    }

    public function destroy(int $id): JsonResponse
    {
        return response()->json(['message' => 'destroy', 'id' => $id]);
    }

    public function noBody(): Response
    {
        return response('Custom')->noContent();
    }

    public function noContent(): Response
    {
        return response()->noContent();
    }

    public function notAllowed(): JsonResponse
    {
        return response()->json(['message' => 'not allowed'], Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function optionalParameter(int $id, string $name = 'default'): JsonResponse
    {
        return response()->json(['message' => 'optional parameter', 'id' => $id, 'name' => $name]);
    }
}
