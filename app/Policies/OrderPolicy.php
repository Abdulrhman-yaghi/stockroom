<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    public function view(User $user, Order $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    public function update(User $user, Order $record): bool
    {
        return false;
    }

    public function delete(User $user, Order $record): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function cancel(User $user, Order $record): bool
    {
        return $user->isAdmin() || ($user->role === 'staff' && $record->user_id === $user->id);
    }
}
