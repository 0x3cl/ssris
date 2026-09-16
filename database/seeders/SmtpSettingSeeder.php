<?php

namespace Database\Seeders;

use App\Models\SmtpSetting;
use Illuminate\Database\Seeder;

class SmtpSettingSeeder extends Seeder
{
    public function run(): void
    {
        $setting = SmtpSetting::query()->first() ?? new SmtpSetting;

        $setting->fill([
            'host' => env('SMTP_SEED_HOST', 'smtp-relay.brevo.com'),
            'port' => env('SMTP_SEED_PORT', '587'),
            'username' => env('SMTP_SEED_USERNAME', ''),
            'password' => env('SMTP_SEED_PASSWORD', ''),
            'from_address' => env('SMTP_SEED_FROM_ADDRESS', ''),
            'from_name' => env('SMTP_SEED_FROM_NAME', 'SRIS'),
        ])->save();
    }
}
