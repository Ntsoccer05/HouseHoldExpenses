<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => '収入', 'en_name' => 'income'],
            ['name' => '支出', 'en_name' => 'expense'],
        ];

        foreach ($types as $type) {
            DB::table('types')->updateOrInsert(
                ['en_name' => $type['en_name']],
                ['name' => $type['name']]
            );
        }
    }
}
