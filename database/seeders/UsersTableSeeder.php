<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        $users = [
            [
                'name' => 'Software Development',
                'email' => 'softwaredevelopment1@ppswallet.de',
                'password' => Hash::make('password123'), // Use a secure password
            ],
            [
                'name' => 'Future Test',
                'email' => 'futuretest45@gmail.com',
                'password' => Hash::make('asdfasdf'), // Use a secure password
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                $user
            );
        }
    }
}
