<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});

Broadcast::channel('notifications.{role}', function (User $user, string $role) {
    return in_array($role, User::ROLES, true) && $user->role === $role;
});
