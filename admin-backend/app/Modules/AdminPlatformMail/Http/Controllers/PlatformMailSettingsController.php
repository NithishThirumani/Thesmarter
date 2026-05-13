<?php

namespace App\Modules\AdminPlatformMail\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AdminPlatformMail\Services\PlatformMailSettingsAdminService;
use App\Support\Mail\PlatformMailConfigurator;
use Aws\Exception\AwsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PlatformMailSettingsController extends Controller
{
    /** @var PlatformMailSettingsAdminService */
    private $platformMailSettings;

    public function __construct(PlatformMailSettingsAdminService $platformMailSettings)
    {
        $this->platformMailSettings = $platformMailSettings;
    }

    /** GET /admin/platform/mail-settings */
    public function show(): JsonResponse
    {
        PlatformMailConfigurator::apply();
        $row = $this->platformMailSettings->getRow();

        return response()->json([
            'success' => true,
            'data' => array_merge(
                $this->platformMailSettings->toApiArray($row),
                ['runtime_effective' => $this->platformMailSettings->runtimeEffective($row)]
            ),
        ]);
    }

    /** PUT /admin/platform/mail-settings */
    public function update(Request $request): JsonResponse
    {
        $validated = PlatformMailSettingsAdminService::validateStoreInput($request);
        $row = $this->platformMailSettings->syncValidated($validated, $request);
        PlatformMailConfigurator::apply();

        return response()->json([
            'success' => true,
            'data' => array_merge(
                $this->platformMailSettings->toApiArray($row),
                ['runtime_effective' => $this->platformMailSettings->runtimeEffective($row)]
            ),
            'message' => 'Mail settings saved. Runtime config updated for new requests.',
        ]);
    }

    /** POST /admin/platform/mail-settings/test */
    public function sendTest(Request $request): JsonResponse
    {
        PlatformMailSettingsAdminService::validateTestInput($request);
        PlatformMailConfigurator::apply();

        $issues = [];

        $fromAddr = config('mail.from.address');
        if (! filled((string) $fromAddr)) {
            $issues[] = 'Set a From email address in Mail settings.';
        }

        $mailer = strtolower((string) config('mail.default', 'smtp'));
        if ($mailer === 'smtp') {
            if (! filled((string) config('mail.mailers.smtp.host'))) {
                $issues[] = 'SMTP host is empty (database and/or MAIL_HOST in .env).';
            }
        }

        if ($mailer === 'ses') {
            if (! filled(config('services.ses.key')) || ! filled(config('services.ses.secret'))) {
                $issues[] = 'SES access key / secret not present in decrypted runtime config.';
            }
            if (! filled((string) config('services.ses.region'))) {
                $issues[] = 'SES region is missing.';
            }
        }

        if ($issues !== []) {
            return response()->json([
                'success' => false,
                'message' => implode(' ', array_unique($issues)),
            ], 422);
        }

        $to = (string) $request->input('test_email');

        $bodyLines = [
            '[Admin portal] Platform mail settings test',
            '',
            'Sent at: '.now()->toDateTimeString(),
            'Effective mailer: '.config('mail.default'),
            'From (runtime): '.(config('mail.from.address') ?: '(not set)'),
            '',
            'Reference — Bizwy SES verified domains commonly include bizwy.in and bizwy.com; ensure From identity matches SES.',
            'If SES is in sandbox mode, this recipient must be verified in SES as well.',
        ];
        $body = implode("\n", $bodyLines);

        /** @var \Illuminate\Mail\MailManager $mailManager */
        $mailManager = app('mail.manager');
        $mailManager->purge();

        try {
            Mail::raw(
                $body,
                function ($message) use ($to): void {
                    $message->to($to)->subject('[Platform] Mail settings test');
                }
            );
        } catch (Throwable $e) {
            $message = $this->formatMailTestFailure($mailer, $e);
            Log::warning('mail.settings.test_failed', [
                'mailer' => $mailer,
                'ses_region' => config('services.ses.region'),
                'exception' => get_class($e),
                'detail' => $message,
            ]);

            return response()->json([
                'success' => false,
                'message' => $message,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Test mail queued/sent.',
        ]);
    }

    private function formatMailTestFailure(string $mailer, Throwable $e): string
    {
        $underlying = $this->underlyingAwsAndTransportDetail($e);
        $combined = $underlying !== '' ? $e->getMessage().' — '.$underlying : $e->getMessage();
        $low = strtolower($combined);
        $hints = [];
        if (str_contains($low, '503') && str_contains($low, 'rcpt')) {
            $hints[] = 'If you intend to use Amazon SES, set Default mailer to ses (HTTPS API). '
                .'"503 RCPT command expected" comes from SMTP (wrong host/port/TLS or IAM keys used as SMTP password).';
        }
        if ($mailer === 'smtp') {
            $hints[] = 'For SES SMTP, create SMTP credentials in the SES console; do not use raw IAM secret access keys as the SMTP password.';
        }
        if ($mailer === 'ses') {
            if (str_contains($low, 'could not resolve host') || str_contains($low, 'nameresolution')) {
                $hints[] = 'DNS/network: this PC cannot resolve AWS endpoints (offline, VPN, or firewall blocking DNS).';
            }
            if (str_contains($low, 'connection timed out') || str_contains($low, 'could not connect to')) {
                $hints[] = 'Outbound HTTPS to AWS may be blocked (firewall/proxy); compare with production egress.';
            }
            if (str_contains($low, 'ssl') || str_contains($low, 'certificate') || str_contains($low, 'curl error 60')) {
                $hints[] = 'TLS failure: enable/fix PHP openssl and curl extensions and CA certificates on this machine.';
            }
            if (preg_match('/invalidaccesskeyid|invalidclienttokenid|signaturedoesnotmatch|securitytokeninvalid/', $low)) {
                $hints[] = 'IAM credentials or region mismatch — verify AWS access key/secret and SES region match the IAM user.';
            }
            if (str_contains($low, 'not verified') || str_contains($low, 'messagerejected') || str_contains($low, 'emailaddressnotverified')) {
                $hints[] = 'SES verification/sandbox: verify From (often @bizwy.in or @bizwy.com) and recipient in SES, or move out of sandbox.';
            }
        }

        return trim($combined.(count($hints) ? ' '.implode(' ', array_unique($hints)) : ''));
    }

    /**
     * Laravel SES transport wraps AwsException; surface AWS message/code for debugging.
     */
    private function underlyingAwsAndTransportDetail(Throwable $e): string
    {
        $segments = [];
        $cursor = $e->getPrevious();
        $depth = 0;
        while ($cursor !== null && $depth < 12) {
            if ($cursor instanceof AwsException) {
                $awsMsg = $cursor->getAwsErrorMessage();
                $code = $cursor->getAwsErrorCode();
                if ($awsMsg !== '') {
                    $segments[] = $awsMsg.($code !== '' ? ' ('.$code.')' : '');
                } elseif ($code !== '') {
                    $segments[] = 'AWS error code: '.$code;
                }
            }
            $msg = $cursor->getMessage();
            if ($msg !== '' && ! in_array($msg, $segments, true)) {
                $segments[] = $msg;
            }
            $cursor = $cursor->getPrevious();
            ++$depth;
        }

        return implode(' — ', $segments);
    }
}
