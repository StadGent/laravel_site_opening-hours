<?php

namespace App\Services;

use App\Formatters\FormatsOpeninghours;
//http://stackoverflow.com/questions/14770898/soapenvelope-soap-envenvelope-php

class VestaService
{
    use FormatsOpeninghours;

    /**
     * Update the opening hours text that resides for a
     * certain service, within VESTA
     *
     * @param  int    $serviceId The ID of the service
     * @param  string $vestaUid  The UID of the service in VESTA
     * @return void
     */
    public function updateOpeninghours($serviceId, $vestaUid)
    {
        $weekSchedule = $this->formatWeek($serviceId);
        dd($weekSchedule);
        // Write the weekschedule to VESTA
    }
}
