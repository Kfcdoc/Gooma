<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

/** RN-20: el cliente solo consulta su propio registro; gestor y admin administran. */
class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Client $client): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // CU-04: el gestor administra clientes; el cliente solo su registro.
        return $user->isGestor()
            || ($user->isCliente() && $user->client_id === $client->getKey());
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isGestor();
    }

    public function update(User $user, Client $client): bool
    {
        return $user->isAdmin() || $user->isGestor();
    }

    public function delete(User $user, Client $client): bool
    {
        return $this->update($user, $client);
    }
}
