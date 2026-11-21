<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\MediaStorage;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function admin(): View
    {
        return $this->render('admin', User::ROLE_ADMIN);
    }

    private function render(string $view, string $role): View
    {
        $user = request()->user();

        abort_unless($user && $user->role === $role, 403);

        return view("dashboards.{$view}", [
            'mediaFolders' => MediaStorage::foldersStatus(),
            'mediaReady' => MediaStorage::isReady(),
        ]);
    }
}
