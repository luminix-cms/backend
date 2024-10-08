<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
// use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('users')->insert([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
        ]);

        DB::table('to_dos')->insert([
            'title' => 'Test To Do',
            'description' => 'This is a test to do.',
            'completed' => false,
            'user_id' => 1,
        ]);

        DB::table('categories')->insert([
            'name' => 'Important',
        ]);

        DB::table('categories')->insert([
            'name' => 'Urgent',
        ]);

        DB::table('categories')->insert([
            'name' => 'Personal',
        ]);

        DB::table('categories')->insert([
            'name' => 'Work',
        ]);

        DB::table('categories')->insert([
            'name' => 'School',
        ]);

        DB::table('categories')->insert([
            'name' => 'Home',
        ]);




    }
}
