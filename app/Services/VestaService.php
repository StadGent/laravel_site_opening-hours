<?php

namespace App\Services;

//http://stackoverflow.com/questions/14770898/soapenvelope-soap-envenvelope-php

/**
 * This class writes text to the VESTA application based on a certain VESTA UID
 */
class VestaService
{
    /**
     * Update the opening hours output that was created for a
     * certain service to the correct VESTA resource based on the VESTA UID
     *
     * @param  string $vestaUid The UID of the service in VESTA
     * @param  string $output   The openinghours output
     * @return void
     */
    public function updateOpeninghours(string $vestaUid, string $output)
    {
        // Write the weekschedule to VESTA using a SOAP call
        \Log::info($output);
    }
}
