<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FetchRecreatex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openinghours:fetch-recreatex';

    /**
     * The SHOP ID for the RECREATEX service
     *
     * @var string
     */
    protected $shopId;

    /**
     * The Recreatex SOAP URI
     *
     * @var string
     */
    protected $recreatexUri;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch RECREATEX openinghours data';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->shopId = env('SHOP_ID');
        $this->recreatexUri = env('RECREATEX_URI');

        if (empty($this->shopId)) {
            throw new \Exception("No shop ID was found, we can't fetch openinghours from the RECREATEX webservice without it.
                You can configure a shop ID in the .env file.");
        }

        if (empty($this->recreatexUri)) {
            throw new \Exception("No recreatexUri was found, we can't fetch openinghours from the RECREATEX webservice without it.
                You can configure a recreatexUri in the .env file.");
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $recreatexServices = app('ServicesRepository')->where('source', 'recreatex')->get();

        foreach ($recreatexServices as $recreatexService) {
            if (! empty($recreatexService->identifier)) {
                $openinghours = $this->getOpeninghours($recreatexService->identifier);

                foreach ($openinghours as $openinghour) {
                    dd($openinghour);
                }
            }
        }
    }

    /**
     * Fetch the recreatex openinghours for a certain infrastructure
     *
     * @param  string $recreatexId
     * @return array
     */
    private function getOpeninghours($recreatexId)
    {
        $soapBody = $this->makeSoapBody($recreatexId);

        $headers = [
            'Content-type: text/xml;charset="utf-8"',
            'Accept: text/xml',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'SOAPAction:  http://www.recreatex.be/webshop/v3.8/IWebShop/FindInfrastructureOpenings',
            'Content-length: ' . strlen($soapBody),
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $this->recreatexUri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $soapBody);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        curl_close($ch);

        // Remove the SOAP envelop
        $response = str_replace('<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body>', '', $response);
        $response = str_replace('</s:Body></s:Envelope>', '', $response);

        $xml = simplexml_load_string($response);
        $json = json_encode($xml);
        $fullJson = json_decode($json, true);

        // Parse the InfrastructureOpeningHours from the body
        return array_get($fullJson, 'InfrastructureOpeningHours.InfrastructureOpeningHours.OpenHours.OpeningHour', []);
    }

    private function makeSoapBody($recreatexId)
    {
        // TODO: delete hard coded value
        $recreatexId = '6d47fc3f-ec23-477b-b38f-d51a0e2a7cf1';

        return '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:v3="http://www.recreatex.be/webshop/v3.8/">
                   <soapenv:Header/>
                   <soapenv:Body>
                      <v3:Context>
                         <v3:ShopId>' . $this->shopId . '</v3:ShopId>
                      </v3:Context>
                      <v3:InfrastructureOpeningsSearchCriteria>
                         <v3:InfrastructureId>' . $recreatexId . '</v3:InfrastructureId>
                         <v3:From>2017-01-01T00:00:00.8115784+02:00</v3:From>
                         <v3:Until>2020-01-01T00:00:00.8115784+02:00</v3:Until>
                      </v3:InfrastructureOpeningsSearchCriteria>
                   </soapenv:Body>
                </soapenv:Envelope>';
    }
}
