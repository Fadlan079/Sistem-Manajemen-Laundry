<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name' => 'ANDIK PELANGGAN',
                'email' => 'pelanggan@test.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('12345678'),
                'phone' => '081234567890',
                'role' => 'pelanggan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'ANDIK ADMIN',
                'email' => 'admin@test.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('12345678'),
                'phone' => '081234567891',
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'ANDIK OPERATOR',
                'email' => 'operator@test.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('12345678'),
                'phone' => '081234567892',
                'role' => 'operator',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'ANDIK KURIR',
                'email' => 'kurir@test.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('12345678'),
                'phone' => '081234567893',
                'role' => 'kurir',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
