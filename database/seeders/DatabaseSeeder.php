<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['phone' => '081312132075'],
            [
                'name' => 'Rhafi Gunawan Bijaksana',
                'role' => 'admin',
                'password' => 'rhafigb09',
                'is_active' => true,
            ]
        );

        SiteSetting::current();
    }
}