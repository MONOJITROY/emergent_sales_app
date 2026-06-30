<?php
namespace App\Core;

final class Mailer
{
    /** Returns [host,port,user,pass,from_email,from_name,secure,replyto,replyto_name] from env vars or DB company settings */
    public static function smtpConfig(): array
    {
        $host = getenv('SMTP_HOST');
        if ($host !== false) {
            return [
                'host'   => $host,
                'port'   => (int)(getenv('SMTP_PORT') ?: 2525),
                'user'   => getenv('SMTP_USER') ?: '',
                'pass'   => getenv('SMTP_PASS') ?: '',
                'secure' => getenv('SMTP_SECURE') ?: 'tls',
                'from'   => getenv('SMTP_FROM') ?: 'no-reply@stockflow.test',
                'name'   => getenv('SMTP_FROM_NAME') ?: 'StockFlow',
                'replyto'       => getenv('SMTP_REPLY_TO') ?: '',
                'replyto_name'  => getenv('SMTP_REPLY_TO_NAME') ?: '',
            ];
        }

        try {
            $row = Database::pdo()->query('SELECT * FROM companies WHERE id = 1')->fetch();
            if ($row) {
                return [
                    'host'   => $row['company_emailhost'] ?? '',
                    'port'   => (int)($row['company_ssl_port'] ?: 587),
                    'user'   => $row['company_emailuser'] ?? '',
                    'pass'   => $row['company_emailpassword'] ?? '',
                    'secure' => $row['company_security'] ?: 'tls',
                    'from'   => $row['company_fromemailid'] ?? '',
                    'name'   => $row['company_fromemailname'] ?? '',
                    'replyto'       => $row['company_replytoemailid'] ?? '',
                    'replyto_name'  => $row['company_replytoemailname'] ?? '',
                ];
            }
        } catch (\Throwable $e) {
            // ignore — table may not exist yet
        }

        return [
            'host'   => '',
            'port'   => 587,
            'user'   => '',
            'pass'   => '',
            'secure' => 'tls',
            'from'   => '',
            'name'   => '',
            'replyto'       => '',
            'replyto_name'  => '',
        ];
    }
}
