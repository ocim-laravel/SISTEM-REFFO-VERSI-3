<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\User;

abstract class OwnerController extends Controller
{
    /**
     * Pengaman kedua setelah middleware role: seluruh fitur Tahap 5 hanya untuk owner.
     */
    protected function pastikanOwner(): void
    {
        abort_unless(request()->user()?->role === User::ROLE_OWNER, 403, 'Fitur ini hanya tersedia untuk Owner.');
    }
}
