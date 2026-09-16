<?php

namespace App\Services;

use App\Models\SmtpSetting;

class SmtpMailerConfigurator
{
    /**
     * Point the "smtp" mailer and the default from-address/name at the
     * admin-managed SMTP settings, instead of the .env-based mail config.
     */
    public function configure(SmtpSetting $smtpSetting): void
    {
        config([
            'mail.mailers.smtp.host' => $smtpSetting->host,
            'mail.mailers.smtp.port' => $smtpSetting->port,
            'mail.mailers.smtp.username' => $smtpSetting->username,
            'mail.mailers.smtp.password' => $smtpSetting->password,
            'mail.from.address' => $smtpSetting->from_address ?: $smtpSetting->username,
            'mail.from.name' => $smtpSetting->from_name ?: config('app.name'),
        ]);
    }
}
