<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PlatformMailSetting extends Model
{
    protected $table = 'platform_mail_settings';

    protected $fillable = [
        'enabled',
        'default_mailer',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'smtp_password',
        'from_address',
        'from_name',
        'aws_access_key_id',
        'aws_secret_access_key',
        'aws_default_region',
        'mailgun_domain',
        'mailgun_secret',
        'postmark_token',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'smtp_port' => 'integer',
    ];

    /** @see App\Support\Mail\PlatformMailConfigurator */
    public static function singleton(): self
    {
        /** @var self $row */
        $row = static::query()->orderBy('id')->first();

        return $row ?: new static;
    }

    public function decryptField(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Encrypted-at-rest payloads decrypt here; SES IAM keys are stored plaintext in DB (see AdminPlatformMail sync).
     */
    public function decryptFieldOrPlain(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return trim($value);
        }
    }

    public function getDecryptedSmtpPassword(): ?string
    {
        return $this->decryptField($this->attributes['smtp_password'] ?? null);
    }

    public function getDecryptedAwsAccessKey(): ?string
    {
        return $this->decryptFieldOrPlain($this->attributes['aws_access_key_id'] ?? null);
    }

    public function getDecryptedAwsSecret(): ?string
    {
        return $this->decryptFieldOrPlain($this->attributes['aws_secret_access_key'] ?? null);
    }

    public function getDecryptedMailgunSecret(): ?string
    {
        return $this->decryptField($this->attributes['mailgun_secret'] ?? null);
    }

    public function getDecryptedPostmarkToken(): ?string
    {
        return $this->decryptField($this->attributes['postmark_token'] ?? null);
    }

    public function setEncryptedField(string $column, ?string $plain): void
    {
        if ($plain === null || $plain === '') {
            $this->attributes[$column] = null;

            return;
        }
        $this->attributes[$column] = Crypt::encryptString($plain);
    }
}
