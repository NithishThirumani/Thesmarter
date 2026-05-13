<?php

use App\Support\PaymentMethodAllowedTypes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * `payment_methods.payment_type`: bounded string category — {@see PaymentMethodAllowedTypes}.
 *
 * Note: MODIFY can fail with InnoDB errno 194 ("Tablespace is missing") if the table is damaged.
 * This migration skips ALTER when the column is already a wide enough VARCHAR and only normalizes row data.
 */
class PaymentMethodsPaymentTypeToAllowedStrings extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_methods') || ! Schema::hasColumn('payment_methods', 'payment_type')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        $info = $this->getPaymentTypeColumnInfo();
        if ($info === null) {
            return;
        }

        if ($this->shouldAlterColumnToVarchar64($info)) {
            try {
                DB::statement('ALTER TABLE `payment_methods` MODIFY `payment_type` VARCHAR(64) NULL');
            } catch (\Throwable $e) {
                $this->handleAlterFailure($e);
            }
        }

        $this->migratePaymentTypeValues();
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_methods') || ! Schema::hasColumn('payment_methods', 'payment_type')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        try {
            DB::statement('ALTER TABLE `payment_methods` MODIFY `payment_type` INT NULL DEFAULT NULL');
            DB::table('payment_methods')->update(['payment_type' => 0]);
        } catch (\Throwable $e) {
            Log::warning('payment_methods down() skipped: '.$e->getMessage());
        }
    }

    /**
     * @return object{data_type: string, char_max_length: ?int, column_type: string}|null
     */
    private function getPaymentTypeColumnInfo(): ?object
    {
        $row = DB::selectOne(
            'SELECT DATA_TYPE AS data_type, CHARACTER_MAXIMUM_LENGTH AS char_max_length, COLUMN_TYPE AS column_type
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?',
            ['payment_methods', 'payment_type']
        );

        return $row ?: null;
    }

    /**
     * @param  object{data_type: string, char_max_length: ?int, column_type: string}  $info
     */
    private function shouldAlterColumnToVarchar64(object $info): bool
    {
        $t = strtolower((string) $info->data_type);

        if (in_array($t, ['tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint'], true)) {
            return true;
        }

        if (in_array($t, ['varchar', 'char'], true)) {
            $len = $info->char_max_length !== null ? (int) $info->char_max_length : 0;

            return $len < 32;
        }

        if (in_array($t, ['text', 'mediumtext', 'longtext', 'tinytext'], true)) {
            return false;
        }

        // Unknown type: attempt ALTER so app rules can store enum strings
        return true;
    }

    private function migratePaymentTypeValues(): void
    {
        foreach (DB::table('payment_methods')->select('payment_id', 'payment_type')->orderBy('payment_id')->cursor() as $row) {
            $next = PaymentMethodAllowedTypes::migrateFromLegacy($row->payment_type);
            if ((string) $row->payment_type !== $next) {
                DB::table('payment_methods')
                    ->where('payment_id', $row->payment_id)
                    ->update(['payment_type' => $next]);
            }
        }
    }

    private function handleAlterFailure(\Throwable $e): void
    {
        $msg = $e->getMessage();
        Log::error('payment_methods payment_type ALTER failed: '.$msg);

        $hint = <<<'TXT'

Database refused to change `payment_methods.payment_type` (often InnoDB errno 194 "Tablespace is missing" = damaged or orphaned `.ibd` / table dictionary mismatch).

Repair the table on the server, then run ONE of:
  • If the column is already VARCHAR(32+) with valid strings, re-run migrations (this migration skips ALTER when schema matches).
  • Otherwise, after backup: `CHECK TABLE payment_methods;` — you may need to restore `payment_methods` from a clean backup or recreate the table and re-import rows.

Manual SQL once the table is healthy:
  ALTER TABLE `payment_methods` MODIFY `payment_type` VARCHAR(64) NULL;

Original error:
TXT;

        throw new \RuntimeException($hint.$msg, 0, $e);
    }
}
