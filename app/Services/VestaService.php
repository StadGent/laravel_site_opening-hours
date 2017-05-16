<?php

namespace App\Services;

/**
 * This class writes text to the VESTA application based on a certain VESTA UID
 * Kudos to stackoverflow so that ancient protocols can still be used: http://stackoverflow.com/questions/14770898/soapenvelope-soap-envenvelope-php
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
    public function updateOpeninghours($vestaUid, $output)
    {
        \Log::info($output);
        return;
        // Write the weekschedule to VESTA using a SOAP call
        $userName = base64_encode(env('VESTA_USER', ''));
        $password = base64_encode(env('VESTA_PASSWORD', ''));
        $soapUrl = env('VESTA_ENDPOINT');

        $soapBody = $this->makeSoapBody($vestaUid, $output, $userName, $password);

        $headers = [
            'Content-type: text/xml;charset="utf-8"',
            'Accept: text/xml',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'SOAPAction:  http://tempuri.org/IVestaMaster/FillHours',
            'Content-length: ' . strlen($soapBody),
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $soapUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $userName . ':' . $password);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $soapBody);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        curl_close($ch);

        // Get the response in the most easiest way
        preg_match('#.*<FillHoursResult>(.*?)</FillHoursResult>.*#', $response, $results);

        if (! empty($results[1])) {
            if ($results[1] == 'true') {
                return true;
            } else {
                \Log::error('Something went wrong while writing the information to VESTA.', [
                    'response' => $response
                ]);
            }
        } else {
            \Log::error('Something went wrong while writing the information to VESTA.', [
                'response' => $response
            ]);
        }

        return false;
    }

    private function makeSoapBody($vestaUid, $output, $userName, $password)
    {
        return '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tem="http://tempuri.org/" xmlns:ves="http://schemas.datacontract.org/2004/07/VestaDataMaster.Models">
                    <soapenv:Header/>
                    <soapenv:Body>
                    <tem:FillHours>
                    <tem:cred>
                    <!--GENTGRP in base64-->
                    <ves:Domain>R0VOVEdSUA==</ves:Domain>
                    <!--password in base 64-->
                    <ves:Password>' . $password . '</ves:Password>
                    <!--sys_a_wsvesta_opuren in base64-->
                    <ves:Username>' . $userName . '</ves:Username>
                </tem:cred>
                <tem:accountId>' . $vestaUid . '</tem:accountId>
                <tem:hours><![CDATA[' . $output . ']]></tem:hours>
            </tem:FillHours>
            </soapenv:Body>
            </soapenv:Envelope>';
    }
}
