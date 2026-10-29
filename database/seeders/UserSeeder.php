<?php

namespace Database\Seeders;

use App\Models\Mitra;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['username' => '199001012015011001'], [
            'name'      => 'Admin Contoh',
            'role'      => 'admin',
            'password'  => Hash::make('password'),
            'is_active' => true,
        ]);

        User::updateOrCreate(['username' => '199002022016022002'], [
            'name'      => 'Koordinator Contoh',
            'role'      => 'koordinator',
            'password'  => Hash::make('password'),
            'is_active' => true,
        ]);

        $akunMitra = User::updateOrCreate(['username' => '3201000001'], [
            'name'      => 'Mitra Contoh',
            'role'      => 'mitra',
            'password'  => Hash::make('password'),
            'is_active' => true,
        ]);

        Mitra::updateOrCreate(['user_id' => $akunMitra->id], [
            'nik'     => '7471000000000001',
            'wilayah' => 'Kendari',
        ]);
    }
}
