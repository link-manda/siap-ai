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
        ];

        $adminItems = [
            [
                'icon' => 'clipboard-document-list',
                'name' => 'Audit Trail',
                'path' => '/audit-trail',
            ],
        ];

        if (auth()->check() && auth()->user()->can('viewAny', User::class)) {
            $adminItems[] = [
                'icon' => 'users',
                'name' => 'Pengguna & Role',
                'path' => '/users',
            ];
        }

        if (auth()->check() && auth()->user()->can('viewAny', AiSetting::class)) {
            $adminItems[] = [
                'icon' => 'cog-6-tooth',
                'name' => 'Pengaturan AI',
                'path' => '/settings/ai',
            ];
        }

        if (! empty($adminItems)) {
            $items[] = [
                'group' => 'Administrator',
                'icon' => 'shield-check', // Optional icon if needed
                'items' => $adminItems,
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

        // Pengecualian eksklusif agar Manajemen Arsip tidak menyala saat di menu Upload Arsip (AI)
        if ($trimmed === 'archives' && request()->is('archives/create')) {
            return false;
        }

        return request()->is($trimmed) || request()->is($trimmed.'/*');
    }
}
