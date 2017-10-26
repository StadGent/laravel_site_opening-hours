<?php

use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;

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
    public function doRequest($method, $path, $params = [])
    {
        if ($this->debug) {
            Log::debug($method . ' -> ' . $path);
        }
        $request = isset($params['format']) ?: 'json';

        $formats = [
            'json' => 'application/json',
            'json-ld' => 'application/ld+json',
            'html' => 'text/html',
            'text' => 'text/plain',
        ];
        $accept = $formats[$request];

        $content = json_encode($params);

        $headers = [
            'CONTENT_LENGTH' => mb_strlen($content, '8bit'),
            'CONTENT_TYPE' => $accept,
            'Accept-Language' => 'nl-BE,nl;q=0.8,en-US;q=0.6,en;q=0.4',
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' =>  $accept,
            'Accept-type' => $accept,
        ];
        $this->call(
            $method,
            $path,
            [],
            [],
            [],
            $this->transformHeadersToServerVars($headers),
            $content
        );
        return $this;
    }

    /**
     *
     * get contect from call
     * and do base tests
     *
     * @param uri $call
     * @return array
     */
    public function getContentStructureTested()
    {
        // check status code
        $this->seeStatusCode(200);
        $content = $this->decodeResponseJson();
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
