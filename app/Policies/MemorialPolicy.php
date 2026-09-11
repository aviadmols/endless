<?php

namespace App\Policies;

use App\Models\Memorial;
use App\Models\User;

class MemorialPolicy
{
    public function before(User $user): ?bool
    {
        return $user->is_admin ? true : null;
    }

    public function view(User $user, Memorial $memorial): bool
    {
        return $memorial->user_id === $user->id;
    }

    public function update(User $user, Memorial $memorial): bool
    {
        return $memorial->user_id === $user->id;
    }

    public function delete(User $user, Memorial $memorial): bool
    {
        return $memorial->user_id === $user->id;
    }
}
