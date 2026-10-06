<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['name' => 'admin'],
            [
                'email' => 'admin@autotrace.com',
                'password' => \Illuminate\Support\Facades\Hash::make('123'),
            ]
        );

        $this->call([
            PlateEntrySeeder::class,
        ]);
    }
}
