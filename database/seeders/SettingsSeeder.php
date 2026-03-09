<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Setting::updateOrCreate(
            ['key' => 'site_name'],
            ['value' => 'BannerArchive', 'type' => 'string', 'group' => 'general'],
        );

        \App\Models\Setting::updateOrCreate(
            ['key' => 'site_description'],
            ['value' => 'Film ve dizi banner arşivi', 'type' => 'string', 'group' => 'general'],
        );

        \App\Models\Setting::updateOrCreate(
            ['key' => 'tmdb_api_key'],
            ['value' => '', 'type' => 'string', 'group' => 'api'],
        );

        \App\Models\Setting::updateOrCreate(
            ['key' => 'primary_color'],
            ['value' => '#d946ef', 'type' => 'string', 'group' => 'appearance'],
        );

        \App\Models\Setting::updateOrCreate(
            ['key' => 'maintenance_mode'],
            ['value' => 'false', 'type' => 'boolean', 'group' => 'system'],
        );
    }
}
