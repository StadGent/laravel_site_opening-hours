<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class CreateDefaultCalendarAndDefaultEvents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('default_calendars', function ($table) {
            $table->increments('id');
            $table->string('label', 255);
            $table->timestamps();
        });

        Schema::create('default_events', function ($table) {
            $table->increments('id');
            $table->text('rrule');
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->integer('calendar_id')->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('default_calendars');
        Schema::drop('default_events');
    }
}
