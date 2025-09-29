<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $email = 'ivanovdo@bk.ru';

        if (!DB::table('users')->where('email', $email)->exists()) {
            DB::table('users')->insert([
                'name' => 'Admin',
                'email' => $email,
                'password' => Hash::make('12345678'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('users')->where('email', 'ivanovdo@bk.ru')->delete();
    }
};

