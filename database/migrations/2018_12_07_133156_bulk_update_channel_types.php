<?php

use App\Models\Channel;
use App\Models\Service;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BulkUpdateChannelTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $general = DB::table('types')
            ->select('id')
            ->where('name', 'like', 'Algemeen')
            ->first()->id;

        $appointment = DB::table('types')
            ->select('id')
            ->where('name', 'like', 'Na afspraak')
            ->first()->id;

        //• Kanalen genaamd “Algemeen” zijn van het type “Algemeen”
        DB::table('channels')->where('label', 'like', '%algemeen%')
            ->where('type_id', '=', null)
            ->update([
                'type_id' => $general,
            ]);

        //• Kanalen met “afspraak” (case insensitive) in de naam zijn van het type “Op afspraak”
        DB::table('channels')->where('label', 'like', '%afspraak%')
            ->where('type_id', '=', null)
            ->update([
                'type_id' => $appointment,
            ]);

        //• Indien na bovenstaande regels het eerste kanaal van een dienst nog geen type heeft, dan wordt het eerste kanaal gemarkeerd als type “Algemeen”
        foreach (Service::has('channels')->get() as $service) {
            $firstChannel = $service->channels[0];
            if ($firstChannel->type === null) {
                $firstChannel->type_id = $general;
                $firstChannel->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
