<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class OpeninghourModelInitialSetup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('services', function ($table) {
            $table->increments('id');
            $table->string('uri', 255);
            $table->string('label', 255);
            $table->text('description');
            $table->timestamps();
        });

        Schema::create('channels', function ($table) {
            $table->increments('id');
            $table->string('label', 255);
            $table->integer('service_id')->unsigned();
            $table->timestamps();
        });

        Schema::create('openinghours', function ($table) {
            $table->increments('id');
            $table->boolean('active');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('label', 255);
            $table->integer('channel_id')->unsigned();
            $table->timestamps();
        });

        Schema::create('calendars', function ($table) {
            $table->increments('id');
            $table->integer('priority');
            $table->text('summary');
            $table->string('label', 255);
            $table->integer('openinghours_id')->unsigned();
            $table->timestamps();
        });

        Schema::create('events', function ($table) {
            $table->increments('id');
            $table->text('rrule');
            $table->date('start_date');
            $table->date('end_date');
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
        Schema::drop('services');
        Schema::drop('channels');
        Schema::drop('openinghours');
        Schema::drop('calendars');
        Schema::drop('events');
    }
}
