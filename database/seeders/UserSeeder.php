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
        User::updateOrCreate(['username' => 'admin'], [
            'name'      => 'Admin Contoh',
            'role'      => 'admin',
            'password'  => Hash::make('admin'),
            'is_active' => true,
        ]);

        User::updateOrCreate(['username' => 'koor'], [
            'name'      => 'Koordinator Contoh',
            'role'      => 'koordinator',
            'password'  => Hash::make('password'),
            'is_active' => true,
        ]);

        $akunMitra = User::updateOrCreate(['username' => 'mitra'], [
            'name'      => 'Mitra 1',
            'role'      => 'mitra',
            'password'  => Hash::make('password'),
            'is_active' => true,
        ]);

        Mitra::updateOrCreate(['user_id' => $akunMitra->id], [
            'nik'     => '7471000000000011',
            'wilayah' => 'Kendari',
        ]);
    }
}
