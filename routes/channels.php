<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return $user->id === $id;
});

Broadcast::channel('admin.attendance', function ($user) {
    return $user->hasAnyRole(['admin', 'manager', 'hr']);
});
