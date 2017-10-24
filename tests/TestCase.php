<?php

abstract class TestCase extends Illuminate\Foundation\Testing\TestCase
{
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * @var string
     */
    protected $apiUrl = '/api';

    /**
     * @var mixed
     */
    protected $debug = false;

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * assemble the path on the given params
     */
    protected function assemblePath($params)
    {
        return $params;
    }

    /**
     * do request according to the given format
     */
    public function doRequest($verb, $path, $params = [])
    {
        if ($this->debug) {
            Log::debug($verb . ' -> ' . $path);
        }
        if (isset($params['format']) && $params['format'] !== 'json') {
            return $this->call($verb, $path);
        }

        return $this->json(
            $verb,
            $path,
            $params,
            [
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip, deflate',
                'Accept-Language' => 'nl-NL,nl;q=0.8,en-US;q=0.6,en;q=0.4',
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept-type' => 'application/json',
            ]
        );
    }

    /**
     *
     * get contect from call
     * and do base tests
     *
     * @param uri $call
     * @return array
     */
    public function getContentStructureTested($call)
    {
        // check status code
        $call->seeStatusCode(200);
        $content = $call->decodeResponseJson();
        // set extra checks
        $this->extraStructureTest($content);

        return $content;
    }

    /**
     * @param $content
     */
    protected function extraStructureTest($content)
    {
    }
}
