<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Partner;
use App\Models\Period;
use App\Models\Task;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@bps-demo.test'],
            ['name' => 'Admin BPS Demo', 'password' => Hash::make('Demo123!'), 'role' => 'admin', 'is_active' => true]
        );

        $koordinator1 = User::updateOrCreate(
            ['email' => 'koordinator1@bps-demo.test'],
            ['name' => 'Rina Koordinator', 'password' => Hash::make('Demo123!'), 'role' => 'koordinator', 'is_active' => true]
        );

        $koordinator2 = User::updateOrCreate(
            ['email' => 'koordinator2@bps-demo.test'],
            ['name' => 'Andi Koordinator', 'password' => Hash::make('Demo123!'), 'role' => 'koordinator', 'is_active' => true]
        );

        $mitraUsers = [];
        $mitraData = [
            ['Mitra Demo 01', 'mitra1@bps-demo.test', 'MIT-001', $koordinator1],
            ['Mitra Demo 02', 'mitra2@bps-demo.test', 'MIT-002', $koordinator1],
            ['Mitra Demo 03', 'mitra3@bps-demo.test', 'MIT-003', $koordinator2],
            ['Mitra Demo 04', 'mitra4@bps-demo.test', 'MIT-004', $koordinator2],
        ];

        foreach ($mitraData as [$name, $email, $code, $coordinator]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => Hash::make('Demo123!'), 'role' => 'mitra', 'is_active' => true]
            );

            $partner = Partner::updateOrCreate(
                ['partner_code' => $code],
                [
                    'user_id' => $user->id,
                    'phone' => '08' . str_pad((string) (100000000 + count($mitraUsers)), 9, '0', STR_PAD_LEFT),
                    'address' => 'Alamat demo, Kendari',
                    'assigned_coordinator_id' => $coordinator->id,
                    'status' => 'active',
                ]
            );
            $mitraUsers[] = [$user, $partner];
        }

        $period = Period::updateOrCreate(
            ['name' => 'Oktober 2026'],
            [
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
                'wage_limit' => 3500000,
                'status' => 'active',
            ]
        );

        $tasks = [
            [$mitraUsers[0][1], 'Pendataan usaha tahap 1', 1000000, '2026-10-20', 'in_progress', 60, $koordinator1],
            [$mitraUsers[0][1], 'Verifikasi data lapangan', 850000, '2026-10-25', 'assigned', 0, $koordinator1],
            [$mitraUsers[1][1], 'Pemeriksaan kelengkapan dokumen', 1250000, '2026-10-23', 'submitted', 100, $koordinator1],
            [$mitraUsers[2][1], 'Pendataan usaha tahap 2', 1500000, '2026-10-27', 'in_progress', 35, $koordinator2],
            [$mitraUsers[3][1], 'Validasi data responden', 750000, '2026-10-29', 'completed', 100, $koordinator2],
        ];

        foreach ($tasks as [$partner, $title, $wage, $dueDate, $status, $progress, $creator]) {
            Task::updateOrCreate(
                ['partner_id' => $partner->id, 'period_id' => $period->id, 'title' => $title],
                [
                    'created_by' => $creator->id,
                    'description' => 'Tugas contoh untuk pengujian tampilan dan alur website.',
                    'wage_amount' => $wage,
                    'due_date' => $dueDate,
                    'status' => $status,
                    'progress' => $progress,
                    'assigned_at' => now(),
                    'completed_at' => $status === 'completed' ? now() : null,
                ]
            );
        }
    }
}
