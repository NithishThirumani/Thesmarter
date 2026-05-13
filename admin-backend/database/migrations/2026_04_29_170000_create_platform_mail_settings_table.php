<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePlatformMailSettingsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('platform_mail_settings')) {
            return;
        }

        Schema::create('platform_mail_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(true)->comment('When false, Laravel uses config/.env mail only.');
            $table->string('default_mailer', 32)->nullable()->comment('smtp, ses, log, array, mailgun, postmark, sendmail');

            $table->string('smtp_host')->nullable();
            $table->unsignedSmallInteger('smtp_port')->nullable();
            $table->string('smtp_encryption', 16)->nullable()->comment('tls, ssl or null');
            $table->string('smtp_username')->nullable();
            $table->mediumText('smtp_password')->nullable()->comment('AES encrypted');

            $table->string('from_address')->nullable();
            $table->string('from_name')->nullable();

            $table->mediumText('aws_access_key_id')->nullable()->comment('Encrypted when set');
            $table->mediumText('aws_secret_access_key')->nullable()->comment('Encrypted when set');
            $table->string('aws_default_region', 48)->nullable();

            $table->string('mailgun_domain')->nullable();
            $table->mediumText('mailgun_secret')->nullable()->comment('Encrypted when set');

            $table->mediumText('postmark_token')->nullable()->comment('Encrypted when set');

            $table->timestamps();
        });

        DB::table('platform_mail_settings')->insertOrIgnore([
            'id' => 1,
            'enabled' => true,
            'default_mailer' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('platform_mail_settings');
    }
}
