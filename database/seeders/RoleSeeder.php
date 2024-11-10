<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run()
    {
        DB::table('role')->insert([
            [
                'id' => 1,
                'name' => 'admin',
                'description' => 'Administrateur',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 2,
                'name' => 'user',
                'description' => 'Utilisateur standard',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 3,
                'name' => 'merchant',
                'description' => 'Marchand',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }
}