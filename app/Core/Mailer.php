<?php
namespace App\Core;

final class Mailer
{
    /** Returns [host,port,user,pass,from_email,from_name,secure] from config or env */
    public static function smtpConfig(): array
    {
        return [
            'host'   => getenv('SMTP_HOST') ?: 'smtp.mailtrap.io',
            'port'   => (int)(getenv('SMTP_PORT') ?: 2525),
            'user'   => getenv('SMTP_USER') ?: '',
            'pass'   => getenv('SMTP_PASS') ?: '',
            'secure' => getenv('SMTP_SECURE') ?: 'tls',
            'from'   => getenv('SMTP_FROM') ?: 'no-reply@stockflow.test',
            'name'   => getenv('SMTP_FROM_NAME') ?: 'StockFlow',
        ];
    }
}
