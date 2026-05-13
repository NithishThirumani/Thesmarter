<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterFeatureTypeInAppFeaturesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('app_features')) {
            return;
        }

        // Legacy DBs may have app_features without feature_type; ->change() then throws from Doctrine.
        if (! Schema::hasColumn('app_features', 'feature_type')) {
            Schema::table('app_features', function (Blueprint $table) {
                $table->string('feature_type', 120)->nullable();
            });

            return;
        }

        Schema::table('app_features', function (Blueprint $table) {
            $table->string('feature_type', 120)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('app_features', function (Blueprint $table) {
            // we leave it as is or revert to original (unknown original length, maybe char(1))
        });
    }
}
