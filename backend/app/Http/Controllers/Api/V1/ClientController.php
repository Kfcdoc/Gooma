<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Clients\StoreClientRequest;
use App\Http\Requests\Clients\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** CU-04 Gestionar clientes, con alcance por rol (RN-20). */
class ClientController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Client::class);

        return ClientResource::collection(
            Client::visibleBy($request->user())->orderBy('name')->get(),
        );
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        Gate::authorize('create', Client::class);

        $client = Client::create($request->validated());

        return (new ClientResource($client))->response()->setStatusCode(201);
    }

    public function show(Client $client): ClientResource
    {
        Gate::authorize('view', $client);

        return new ClientResource($client);
    }

    public function update(UpdateClientRequest $request, Client $client): ClientResource
    {
        Gate::authorize('update', $client);

        $client->update($request->validated());

        return new ClientResource($client);
    }

    public function destroy(Client $client): Response
    {
        Gate::authorize('delete', $client);

        if ($client->projects()->exists() || $client->users()->exists()) {
            throw ValidationException::withMessages([
                'client' => ['No se puede eliminar un cliente con proyectos o usuarios asociados.'],
            ]);
        }

        $client->delete();

        return response()->noContent();
    }
}
