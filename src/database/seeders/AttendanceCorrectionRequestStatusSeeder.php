<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// attendance_correction_request_statuses テーブルの固定マスタを登録する Seeder
class AttendanceCorrectionRequestStatusSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('attendance_correction_request_statuses')->upsert(
            [
                [
                    'code' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'approved',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            ['code'],
            ['updated_at']
        );
    }
}
