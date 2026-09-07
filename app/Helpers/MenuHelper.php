<?php

namespace App\Helpers;

use App\Models\AiSetting;
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

        if (auth()->check() && auth()->user()->can('viewAny', AiSetting::class)) {
            $items[] = [
                'icon' => 'cog-6-tooth',
                'name' => 'Pengaturan AI',
                'path' => '/settings/ai',
            ];
        }

        return $items;
    }

    public static function isActive($path)
    {
        $trimmed = ltrim($path, '/');

        if ($trimmed === '') {
            return request()->is('/');
        }

        return request()->is($trimmed) || request()->is($trimmed.'/*');
    }
}
