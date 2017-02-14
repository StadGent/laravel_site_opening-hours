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
     * @param  int  $serviceId
     * @return void
     */
    public function updateOpeninghours($serviceId)
    {
        $weekSchedule = $this->formatWeek($serviceId);
        dd($weekSchedule);
        // Write the weekschedule to VESTA
    }
}
