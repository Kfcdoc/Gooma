<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/** RN-20/RN-21: el cliente solo consulta su proyecto; el gestor solo los suyos. */
class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return $project->isVisibleBy($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isGestor();
    }

    public function update(User $user, Project $project): bool
    {
        return $project->isEditableBy($user);
    }

    public function delete(User $user, Project $project): bool
    {
        return $project->isEditableBy($user);
    }
}
