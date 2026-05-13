<?php

namespace App\Services;

use App\Models\AdminNotification;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class AdminNotificationService
{
    /**
     * @param  'info'|'success'|'warning'|'error'  $kind
     */
    public static function record(string $kind, string $title, string $body): void
    {
        try {
            if (! Schema::hasTable('admin_notifications')) {
                return;
            }
        } catch (Throwable $e) {
            return;
        }

        $allowed = ['info', 'success', 'warning', 'error'];
        if (! in_array($kind, $allowed, true)) {
            $kind = 'info';
        }

        AdminNotification::query()->create([
            'kind' => $kind,
            'title' => mb_substr($title, 0, 160),
            'body' => $body,
        ]);
    }
}
