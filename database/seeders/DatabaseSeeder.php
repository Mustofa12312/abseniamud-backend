<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Insert Roles
        $roleSuperAdmin = DB::table('roles')->insertGetId([
            'name' => 'super_admin',
            'description' => 'Super Administrator',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        
        $roleDosen = DB::table('roles')->insertGetId([
            'name' => 'dosen',
            'description' => 'Dosen Pengajar',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Insert Admin User
        DB::table('users')->insert([
            'name' => 'Super Admin',
            'email' => 'admin@iaimu.ac.id',
            'password' => Hash::make('password'),
            'role_id' => $roleSuperAdmin,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Insert Dosen User
        $dosenUserId = DB::table('users')->insertGetId([
            'name' => 'Ahmad',
            'email' => 'ahmad@iaimu.ac.id',
            'password' => Hash::make('password'),
            'role_id' => $roleDosen,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Insert Lecturer profile
        DB::table('lecturers')->insert([
            'user_id' => $dosenUserId,
            'nidn' => '1234567890',
            'nip' => '198001012005011001',
            'phone' => '081234567890',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Insert Location
        DB::table('locations')->insert([
            'name' => 'Gedung A',
            'latitude' => -7.166316,
            'longitude' => 113.483162,
            'radius' => 75,
            'max_accuracy' => 50,
            'is_active' => true,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
