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
    protected $apiUrl = '/api/v1';

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
    public function doRequest($method, $path, $data = [])
    {
        if ($this->debug) {
            Log::debug($method . ' -> ' . $path);
        }
        $formats = [
            'json' => 'application/json',
            'json-ld' => 'application/ld+json',
            'html' => 'text/html',
            'text' => 'text/plain',
        ];

        $format = 'json';
        if (isset($data['format']) && array_key_exists($data['format'], $formats)) {
            $format = $data['format'];
            unset($data['format']);
        }
        $accept = $formats[$format];

        $langs = [
            'nl', 'nl-BE', 'nl-NL',
            'fr', 'fr-BE', 'fr-FR',
            'en', 'en-GB', 'en-US',
            'de', 'de-DE',
            'es', 'es-ES',
        ];
        $lang = 'nl';
        if (isset($data['lang']) && in_array($data['lang'], $langs)) {
            $lang = $data['lang'];
            unset($data['lang']);
        }

        $content = json_encode($data);

        $headers = [
            'CONTENT_LENGTH' => mb_strlen($content, '8bit'),
            'CONTENT_TYPE' => $accept,
            'Accept-Language' => $lang,
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => $accept,
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
        $content = $this->getContentStructureTested();
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

    /**
     * @param $userRole
     * @param $verb
     * @param $pathArg
     * @param $data
     * @param $statusCode
     */
    public function requestsByUserWithRoleAndCheckStatusCode($userRole, $verb, $pathArg, $data, $statusCode)
    {
        if ($userRole !== 'unauth') {
            $authUser = \App\Models\User::where('name', $userRole . 'user')->first();
            $this->actingAs($authUser, 'api');
        }

        $path = $this->assemblePath($pathArg);
        $this->doRequest($verb, $path, $data);
        $this->seeStatusCode($statusCode);
    }
}
