<?php

namespace App\Policies;

use App\Models\Memory;
use App\Models\User;

class MemoryPolicy
{
    public function before(User $user): ?bool
    {
        return $user->is_admin ? true : null;
    }

    public function moderate(User $user, Memory $memory): bool
    {
        return $memory->memorial->user_id === $user->id;
    }

    public function update(User $user, Memory $memory): bool
    {
        return $this->moderate($user, $memory);
    }

    public function delete(User $user, Memory $memory): bool
    {
        return $this->moderate($user, $memory);
    }
}
