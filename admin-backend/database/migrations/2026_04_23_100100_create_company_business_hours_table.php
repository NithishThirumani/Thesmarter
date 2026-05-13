<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_business_hours')) {
            return;
        }

        Schema::create('company_business_hours', function (Blueprint $table) {
            $table->bigIncrements('id');
            // Legacy schema uses int(11) for company_detail.company_id.
            $table->integer('company_id');
            $table->enum('day_of_week', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
            $table->boolean('is_open')->default(false);
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->unsignedTinyInteger('slot_index')->default(1);
            $table->timestamps();

            $table->index(['company_id', 'day_of_week'], 'cbh_company_day_idx');
            $table->unique(['company_id', 'day_of_week', 'slot_index'], 'cbh_company_day_slot_uniq');
            // Avoid FK here to stay compatible with mixed legacy DB definitions across environments.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_business_hours');
    }
};
