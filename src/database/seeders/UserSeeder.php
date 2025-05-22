<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // テストユーザー作成
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test-kakep@example.com',
            'password' => 'kakepassword'
        ]);
    }
}