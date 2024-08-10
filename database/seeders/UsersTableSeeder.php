<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $users = [
            ['full_name'=>'rawanjizzine','email' => 'rawanjizzine38@gmail.com', 'password' => 'rawan2001', 'image' => '', 'type' => 'admin'],
           // Add more users as needed
        ];

        foreach ($users as $user) {
            User::create([
                'full_name'=>$user['full_name'],
                'email' => $user['email'],
                'password' => Hash::make($user['password']),
                'image' => $user['image'],
                'type' => $user['type'],
            ]);
        }
    }
}
