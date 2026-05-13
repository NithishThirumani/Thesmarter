<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_settings')) {
            return;
        }

        Schema::create('company_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            // Legacy schema uses int(11) for company_detail.company_id.
            $table->integer('company_id');
            $table->boolean('enforce_2fa')->default(false);
            $table->boolean('geo_location_tracking')->default(false);
            $table->boolean('geo_fencing_enabled')->default(false);
            $table->unsignedInteger('geo_fencing_radius')->nullable();
            $table->boolean('appointment_time_slice_enabled')->default(false);
            $table->unsignedInteger('appointment_time_slice_minutes')->nullable();
            $table->boolean('auto_approve_appointments')->default(false);
            $table->text('marketing_message')->nullable();
            $table->boolean('public_company_page')->default(false);
            $table->timestamps();

            $table->unique('company_id', 'company_settings_company_unique');
            // Avoid FK here to stay compatible with mixed legacy DB definitions across environments.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
