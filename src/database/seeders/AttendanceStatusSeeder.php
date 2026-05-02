<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// attendance_statuses テーブルの固定マスタを登録する Seeder
class AttendanceStatusSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('attendance_statuses')->upsert(
            [
                [
                    'code' => 'off_duty',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'working',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'on_break',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'finished',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            ['code'],
            ['updated_at']
        );
    }
}
