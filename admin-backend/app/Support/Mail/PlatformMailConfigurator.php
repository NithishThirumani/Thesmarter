<?php



namespace App\Support\Mail;



use App\PlatformMailSetting;

use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\Log;



/**

 * Applies platform_mail_settings row over config/mail + config/services mail drivers (runtime).

 */

final class PlatformMailConfigurator

{

    public static function apply(): void

    {

        try {

            if (! Schema::hasTable('platform_mail_settings')) {

                return;

            }

        } catch (\Throwable $e) {

            return;

        }



        $row = PlatformMailSetting::query()->orderBy('id')->first();

        if (! $row || ! $row->enabled) {

            return;

        }



        if ($row->default_mailer) {

            config(['mail.default' => strtolower(trim((string) $row->default_mailer))]);

        }



        if ($row->from_address) {

            config(['mail.from.address' => $row->from_address]);

        }



        if ($row->from_name !== null && $row->from_name !== '') {

            config(['mail.from.name' => $row->from_name]);

        }



        if ($row->smtp_host) {

            config(['mail.mailers.smtp.host' => $row->smtp_host]);

        }

        if ($row->smtp_port) {

            config(['mail.mailers.smtp.port' => $row->smtp_port]);

        }

        // When overlay is enabled, clear stale MAIL_ENCRYPTION from .env whenever DB leaves encryption unset.

        if ($row->smtp_encryption !== null && trim((string) $row->smtp_encryption) !== '') {

            config(['mail.mailers.smtp.encryption' => strtolower((string) $row->smtp_encryption)]);

        } else {

            config(['mail.mailers.smtp.encryption' => null]);

        }



        if ($row->smtp_username) {

            config(['mail.mailers.smtp.username' => $row->smtp_username]);

        }



        $smtpPass = $row->getDecryptedSmtpPassword();

        if ($smtpPass !== null && $smtpPass !== '') {

            config(['mail.mailers.smtp.password' => $smtpPass]);

        }



        $awsKey = trim((string) $row->getDecryptedAwsAccessKey());

        $awsSecret = trim((string) $row->getDecryptedAwsSecret());

       



        if ($awsKey) {

            config(['services.ses.key' => $awsKey]);

        }

        if ($awsSecret !== null && $awsSecret !== '') {

            config(['services.ses.secret' => $awsSecret]);

        }

        if ($row->aws_default_region) {

            config(['services.ses.region' => $row->aws_default_region]);

        }



        $mgSecret = $row->getDecryptedMailgunSecret();

        if ($row->mailgun_domain) {

            config(['services.mailgun.domain' => $row->mailgun_domain]);

        }

        if ($mgSecret) {

            config(['services.mailgun.secret' => $mgSecret]);

        }



        $pm = $row->getDecryptedPostmarkToken();

        if ($pm) {

            config(['services.postmark.token' => $pm]);

        }

    }

}

