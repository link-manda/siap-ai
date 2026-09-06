<?php

namespace App\Helpers;

use App\Models\User;

class MenuHelper
{
    public static function getMainNavItems()
    {
        $items = [
            [
                'icon' => 'home',
                'name' => 'Dashboard',
                'path' => '/',
            ],
            [
                'icon' => 'folder',
                'name' => 'Manajemen Arsip',
                'path' => '/archives',
            ],
            [
                'icon' => 'arrow-up-tray',
                'name' => 'Upload Arsip (AI)',
                'path' => '/archives/create',
            ],
            [
                'icon' => 'clipboard-document-list',
                'name' => 'Audit Trail',
                'path' => '/audit-trail',
            ],
        ];

        if (auth()->check() && auth()->user()->can('viewAny', User::class)) {
            $items[] = [
                'icon' => 'users',
                'name' => 'Pengguna & Role',
                'path' => '/users',
            ];
        }

        return $items;
    }

    public static function isActive($path)
    {
        return request()->is(ltrim($path, '/'));
    }
}
