<?php

namespace Database\Seeders;

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
        // Admin User
        User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@puskesmas.go.id',
            'role' => 'Admin',
            'password' => bcrypt('password'), // Or use static hash
        ]);

        // Categories
        $categories = ['Surat Keputusan', 'Surat Masuk', 'Surat Keluar', 'Rekam Medis', 'Laporan Keuangan', 'Data Pegawai'];
        foreach ($categories as $category) {
            \App\Models\Category::create(['name' => $category]);
        }

        // Classifications
        \App\Models\Classification::create(['name' => 'Biasa', 'level' => 1]);
        \App\Models\Classification::create(['name' => 'Penting', 'level' => 2]);
        \App\Models\Classification::create(['name' => 'Rahasia', 'level' => 3]);
        \App\Models\Classification::create(['name' => 'Sangat Rahasia', 'level' => 4]);
    }
}
