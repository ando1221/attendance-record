<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// roles テーブルの固定マスタを登録する Seeder
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->upsert(
            [
                [
                    'code' => 'user',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'admin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            ['code'], // 重複判定に使う一意キー
            ['updated_at'] // 重複時に更新するカラム
        );
    }
}
