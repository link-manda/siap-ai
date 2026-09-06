<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Classification;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
        ]);

        // Admin User
        $admin = User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@puskesmas.go.id',
            'password' => bcrypt('password'), // Or use static hash
        ]);

        $admin->assignRole('Administrator');

        // Categories
        $categories = ['Surat Keputusan', 'Surat Masuk', 'Surat Keluar', 'Rekam Medis', 'Laporan Keuangan', 'Data Pegawai'];
        foreach ($categories as $category) {
            Category::create(['name' => $category]);
        }

        // Classifications
        Classification::create(['name' => 'Biasa', 'level' => 1]);
        Classification::create(['name' => 'Terbatas', 'level' => 2]);
        Classification::create(['name' => 'Rahasia', 'level' => 3]);
        Classification::create(['name' => 'Sangat Rahasia', 'level' => 4]);
    }
}
