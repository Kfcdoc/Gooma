<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Catalog\StoreTechnologyRequest;
use App\Http\Requests\Catalog\UpdateTechnologyRequest;
use App\Http\Resources\TechnologyResource;
use App\Models\Technology;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Catálogo de tecnologías (RN-05). */
class TechnologyController
{
    public function index(): AnonymousResourceCollection
    {
        return TechnologyResource::collection(Technology::orderBy('name')->get());
    }

    public function show(Technology $technology): TechnologyResource
    {
        return new TechnologyResource($technology);
    }

    public function store(StoreTechnologyRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] ??= Str::slug($data['name']);

        $technology = Technology::create($data);

        return (new TechnologyResource($technology))->response()->setStatusCode(201);
    }

    public function update(UpdateTechnologyRequest $request, Technology $technology): TechnologyResource
    {
        $data = $request->validated();

        if (isset($data['name']) && ! isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $technology->update($data);

        return new TechnologyResource($technology);
    }

    public function destroy(Technology $technology): Response
    {
        if ($technology->projectTechnologies()->exists()) {
            throw ValidationException::withMessages([
                'technology' => ['La tecnología está asociada a uno o más proyectos.'],
            ]);
        }

        $technology->delete();

        return response()->noContent();
    }
}
