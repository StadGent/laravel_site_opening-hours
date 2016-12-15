<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class AddClosingHoursBooleanToCalendar extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('calendars', function ($table) {
            $table->boolean('closinghours')->default(false);
        });

        Schema::table('default_calendars', function ($table) {
            $table->boolean('closinghours')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('calendars', function ($table) {
            $table->dropColumn('closinghours');
        });

        Schema::table('default_calendars', function ($table) {
            $table->dropColumn('closinghours');
        });
    }
}
