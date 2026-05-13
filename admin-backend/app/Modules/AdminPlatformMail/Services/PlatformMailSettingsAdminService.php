<?php

namespace App\Modules\AdminPlatformMail\Services;

use App\PlatformMailSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlatformMailSettingsAdminService
{
    /** @api GET single settings document for admin portal */
    public function toApiArray(?PlatformMailSetting $row): array
    {
        if (! $row || ! $row->exists) {
            return [
                'persisted_row' => false,
                'enabled' => false,
                'env_preview' => [
                    'mail_default' => config('mail.default'),
                    'from_address' => config('mail.from.address'),
                    'from_name' => config('mail.from.name'),
                ],
                'message' => 'Run migrations and reload — no database row yet. Mail still uses config / .env.',
            ];
        }

        return [
            'persisted_row' => true,
            'enabled' => (bool) $row->enabled,
            'default_mailer' => $row->default_mailer,
            'smtp_host' => $row->smtp_host,
            'smtp_port' => $row->smtp_port,
            'smtp_encryption' => $row->smtp_encryption === null ? '' : $row->smtp_encryption,
            'smtp_username' => $row->smtp_username,
            'smtp_password_set' => filled($row->smtp_password ?? null),
            'from_address' => $row->from_address,
            'from_name' => $row->from_name,
            'aws_default_region' => $row->aws_default_region,
            'mailgun_domain' => $row->mailgun_domain,
            'secrets' => [
                'aws_access_key_set' => filled($row->aws_access_key_id ?? null),
                'aws_secret_set' => filled($row->aws_secret_access_key ?? null),
                'mailgun_secret_set' => filled($row->mailgun_secret ?? null),
                'postmark_token_set' => filled($row->postmark_token ?? null),
            ],
        ];
    }

    /**
     * After PlatformMailConfigurator::apply(), summarizes what Laravel will use for outbound mail.
     *
     * @return array<string, mixed>
     */
    public function runtimeEffective(?PlatformMailSetting $row): array
    {
        $mailer = strtolower((string) config('mail.default', 'smtp'));
        $dbOn = $row !== null && $row->exists && (bool) $row->enabled;
        $storedRaw = ($row !== null && $row->exists) ? $row->default_mailer : null;
        $storedDriver = ($storedRaw !== null && trim((string) $storedRaw) !== '')
            ? strtolower(trim((string) $storedRaw))
            : null;

        $hints = [];

        if (! $dbOn) {
            $hints[] = 'Platform mail DB overlay is OFF — Laravel uses .env and config/mail.php only.';
        } elseif ($storedDriver === null || $storedDriver === '') {
            $hints[] = 'Database default_mailer is blank — Laravel falls back to MAIL_MAILER from .env (often smtp). For Amazon SES HTTPS API choose ses.';
        }

        $fromAddr = config('mail.from.address');
        if (! filled($fromAddr)) {
            $hints[] = 'Set From email — many providers reject mail without it.';
        }

        if ($mailer === 'ses') {
            $keySet = filled(config('services.ses.key'));
            $secretSet = filled(config('services.ses.secret'));
            if (! ($keySet && $secretSet)) {
                $hints[] = 'Mailer ses selected but SES key/secret missing in runtime config.';
            }
            if (! filled((string) config('services.ses.region'))) {
                $hints[] = 'SES region is missing.';
            }
            $hints[] = 'Typical Bizwy verified identities in SES: bizwy.in & bizwy.com (From must match your SES/domain setup). In SES sandbox, recipient emails must be verified too.';
        }

        if ($mailer === 'smtp') {
            $hints[] = 'Using SMTP (not SES HTTP API). For SES SMTP use SMTP credentials from the SES console, or switch default mailer to ses.';
            if (! filled((string) config('mail.mailers.smtp.host'))) {
                $hints[] = 'SMTP host is empty — .env MAIL_HOST may apply.';
            }
        }

        return [
            'active_mailer' => $mailer,
            'db_overlay_active' => $dbOn,
            'stored_default_mailer' => $storedDriver,
            'from_address_effective' => $fromAddr !== null ? (string) $fromAddr : null,
            'from_name_effective' => config('mail.from.name'),
            'smtp' => [
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'username_set' => filled(config('mail.mailers.smtp.username')),
                'password_set' => filled(config('mail.mailers.smtp.password')),
            ],
            'ses' => [
                'region' => config('services.ses.region'),
                'credentials_configured' => filled(config('services.ses.key')) && filled(config('services.ses.secret')),
            ],
            'hints' => array_values(array_unique($hints)),
        ];
    }

    public function getRow(): ?PlatformMailSetting
    {
        return PlatformMailSetting::query()->find(1);
    }

    /**
     * @param array<string,mixed> $input validated fields from request body
     */
    public function syncValidated(array $input, Request $request): PlatformMailSetting
    {
        $row = PlatformMailSetting::query()->find(1);
        if (! $row) {
            $row = new PlatformMailSetting;
            $row->id = 1;
            $row->exists = false;
        }

        if (isset($input['enabled'])) {
            $row->enabled = (bool) $input['enabled'];
        }

        if (array_key_exists('default_mailer', $input)) {
            $v = $input['default_mailer'];
            $row->default_mailer = ($v !== null && $v !== '')
                ? strtolower(trim((string) $v))
                : null;
        }

        foreach (['smtp_host', 'smtp_username', 'from_address', 'from_name', 'aws_default_region', 'mailgun_domain'] as $f) {
            if (! array_key_exists($f, $input)) {
                continue;
            }
            $v = $input[$f];
            $row->{$f} = ($v !== null && $v !== '') ? trim((string) $v) : null;
        }

        if (array_key_exists('smtp_port', $input)) {
            $p = $input['smtp_port'];
            $row->smtp_port = ($p !== null && $p !== '') ? (int) $p : null;
        }

        if (array_key_exists('smtp_encryption', $input)) {
            $enc = $input['smtp_encryption'];
            if ($enc === '' || $enc === null || $enc === '__none') {
                $row->smtp_encryption = null;
            } else {
                $row->smtp_encryption = (string) $enc;
            }
        }

        foreach (['smtp_password', 'aws_access_key_id', 'aws_secret_access_key', 'mailgun_secret', 'postmark_token'] as $secretKey) {
            $this->applySecretFieldFromRequest($row, $request, $secretKey);
        }

        $row->save();
        $row->refresh();

        return $row;
    }

    /**
     * If the JSON key exists: empty string clears; non-empty replaces stored value.
     * If omitted, leave DB column untouched.
     * AWS SES IAM fields are stored plaintext; other secrets use Laravel encryption.
     */
    private function applySecretFieldFromRequest(PlatformMailSetting $row, Request $request, string $secretKey): void
    {
        $payload = $request->all();
        if (! array_key_exists($secretKey, $payload)) {
            return;
        }

        $value = $payload[$secretKey];

        $columnMap = [
            'smtp_password' => 'smtp_password',
            'aws_access_key_id' => 'aws_access_key_id',
            'aws_secret_access_key' => 'aws_secret_access_key',
            'mailgun_secret' => 'mailgun_secret',
            'postmark_token' => 'postmark_token',
        ];
        $dbCol = $columnMap[$secretKey];
        if ($value === null || $value === '') {
            $row->{$dbCol} = null;

            return;
        }

        $plain = trim(is_string($value) ? $value : (string) $value);
        if ($secretKey === 'aws_access_key_id' || $secretKey === 'aws_secret_access_key') {
            $row->{$dbCol} = $plain !== '' ? $plain : null;

            return;
        }

        $row->setEncryptedField($dbCol, $plain);
    }

    public static function validateStoreInput(Request $request): array
    {
        return Validator::make($request->all(), [
            'enabled' => 'required|boolean',
            'default_mailer' => 'nullable|string|in:smtp,ses,log,array,mailgun,postmark,sendmail',
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_encryption' => 'nullable|string|max:16',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:2000',
            'from_address' => 'nullable|email|max:255',
            'from_name' => 'nullable|string|max:255',
            'aws_access_key_id' => 'nullable|string|max:2000',
            'aws_secret_access_key' => 'nullable|string|max:2000',
            'aws_default_region' => 'nullable|string|max:48',
            'mailgun_domain' => 'nullable|string|max:255',
            'mailgun_secret' => 'nullable|string|max:2000',
            'postmark_token' => 'nullable|string|max:2000',
        ])->validate();
    }

    public static function validateTestInput(Request $request): array
    {
        return Validator::make($request->all(), [
            'test_email' => 'required|email|max:255',
        ])->validate();
    }
}
