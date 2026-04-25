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
                'address' => 'Jl. Demo Pelanggan',
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
                'address' => 'Jl. Demo Admin',
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
                'address' => 'Jl. Demo Operator',
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
                'address' => 'Jl. Demo Kurir',
                'role' => 'kurir',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
