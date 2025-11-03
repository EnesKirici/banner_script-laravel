<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DefaultUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
{
    \App\Models\User::updateOrCreate(
        ['email' => 'elw@banner.local'],
        [
            'name' => 'elw',
            'password' => \Hash::make('Enye1824/'),
            'email_verified_at' => now(),
        ]
    );
}
}
