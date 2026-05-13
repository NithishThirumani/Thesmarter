<?php

namespace App\Console\Commands;

use App\Support\Mail\PlatformMailConfigurator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends a single raw text message via the app's default mail transport after applying platform DB overlay.
 *
 * Usage (on API host): php artisan platform:mail-test nizamuddin@tutorialspoint.com
 */
class PlatformMailTestCommand extends Command
{
    protected $signature = 'platform:mail-test {recipient : Destination email address}';

    protected $description = 'Send one test mail using PlatformMailConfigurator (DB overlay) + current mail.default';

    public function handle(): int
    {
        PlatformMailConfigurator::apply();

        /** @var \Illuminate\Mail\MailManager $manager */
        $manager = app('mail.manager');
        $manager->purge();

        $default = strtolower((string) config('mail.default', 'smtp'));
        $this->info('Effective mail.default: '.$default);

        if ($default === 'ses') {
            $this->line('SES region: '.(filled(config('services.ses.region')) ? (string) config('services.ses.region') : '(empty — falls back from config .env defaults)'));
            $this->line('SES access key configured: '.(filled(config('services.ses.key')) ? 'yes' : 'no'));
            $this->line('SES secret configured: '.(filled(config('services.ses.secret')) ? 'yes' : 'no'));
            $from = config('mail.from.address');
            $this->line('From: '.($from !== null && $from !== '' ? (string) $from : '(empty)'));
        } elseif ($default === 'smtp') {
            $this->line('SMTP host: '.(config('mail.mailers.smtp.host') ?: '(empty)'));
            $this->line('SMTP port: '.(config('mail.mailers.smtp.port') ?: '(empty)'));
        }

        $to = trim((string) $this->argument('recipient'));

        try {
            Mail::raw(
                "[platform:mail-test] Sent at ".now()->toIso8601String()."\n\n".
                'Mailer: '.config('mail.default')."\n".
                'Reference: Bizwy SES domains often verified as bizwy.in / bizwy.com; sandbox requires verifying recipients.',
                function ($message) use ($to): void {
                    $message->to($to)->subject('[Platform] Mail test');
                }
            );
        } catch (Throwable $e) {
            $prev = $e->getPrevious();
            $this->error($e->getMessage());
            if ($prev !== null) {
                $this->error('Underlying: '.$prev->getMessage());
            }

            return self::FAILURE;
        }

        $this->info('Queued/sent OK to '.$to);

        return self::SUCCESS;
    }
}
