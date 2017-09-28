<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Psr7\Response;

/**
 * This class is responsible to make requests to and return results from a SPARQL endpoint
 */
class SparqlService
{

    /**
     * @var Client
     */
    private $guzzleClient;

    /**
     * The user name that has access to the SPARQL endpoint
     * @var string
     */
    private $username;

    /**
     * The password of the configured
     * @var string
     */
    private $password;

    /**
     * The default graph to use
     * @var string
     */
    private $defaultGraph;

    /**
     * @var mixed
     */
    private $lastResponseCode;

    /**
     * @param $endpoint
     * @param $username
     * @param $password
     * @param $defaultGraph
     */
    public function __construct($endpoint = null, $username = null, $password = null, $defaultGraph = null)
    {
        $this->username = $username ?: env('SPARQL_WRITE_ENDPOINT_USERNAME');
        $this->password = $password ?: env('SPARQL_WRITE_ENDPOINT_PASSWORD');
        $this->defaultGraph = $defaultGraph ?: env('SPARQL_WRITE_GRAPH');

        $this->setClient($endpoint ?: env('SPARQL_WRITE_ENDPOINT'));
    }

    /**
     * @param $endpoint
     * @param $username
     * @param $password
     */
    public function setClient($endpoint = '')
    {
        // CurlHandler
        // As currently digest authenitcation is currently only supported when using the cURL handler
        // http://docs.guzzlephp.org/en/stable/request-options.html#auth
        $handler = new CurlHandler();
        $options['handler'] = HandlerStack::create($handler); // Wrap w/ middleware
        $options['base_uri'] = $endpoint;

        $this->guzzleClient = new Client($options);

        $this->baseConnectionTest();

    }

    /**
     * @todo  check or reply is of SPARQL
     */
    public function baseConnectionTest($options = [])
    {
        $options['connect_timeout'] = 0.1;
        $query = 'WITH <' . env('SPARQL_WRITE_GRAPH') . '> ASK { ?s ?p ?o }';
        $uri = '?query=' . static::transformQuery($query);
        $result = $this->executeQuery('GET', $uri, $options);

        if (($this->getLastResponceCode() != 200)) {
            throw new \Exception("An error came as reply in connection test", 1);
        }
        $data = json_decode($result);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("No correct reply came back in connection test", 1);
        }

        if ($data !== true) {
            throw new \Exception("No correct data came back in connection test", 1);
        }

    }

    /**
     * Return the response coming from the result of a SPARQL query
     * If the method was not a GET method, return a boolean indicating
     * if the query was performed succesful or not
     *
     * @deprecated
     * @param  string $query
     * @param  string $method
     * @param  string $format
     * @return mixed
     */
    public function performSparqlQuery($query, $method = 'GET', $format = null)
    {
        try {
            switch ($method) {
                case 'POST':
                    $this->post($query, $format);

                    return $this->lastResponseCode === 200;
                case 'GET':
                    return $this->get($query, $format);
                default:
                    throw new \Exception('Given method: ' . $method . ' is not supported by this SparqlService');
            }
        } catch (\Exception $ex) {
            \Log::error('Something went wrong while writing to the LOD repository: ' . $ex->getMessage());
        }

        return false;
    }

    /**
     * Prepare and perform a POST to the SparQL
     *
     * @param $query
     * @return mixed
     */
    public function post($query, $format = null)
    {
        -
        $this->lastResponseCode = null;
        $uri = '?graph-uri=' . static::transformQuery($this->defaultGraph) . '&format=' . ($format ?: 'json');
        $options = ['form_params' => ['query' => $query]];

        return $this->executeQuery('POST', $uri, $options);
    }

    /**
     * Prepare and perform a GET to the SparQL
     *
     * @param $query
     * @param $format
     * @return mixed
     */
    public function get($query, $format = null)
    {
        //  \Log::info('SPARQL query GET: ' . $query);
        $this->lastResponseCode = null;
        $uri = '?query=' . static::transformQuery($query) . '&format=' . ($format ?: 'json');

        return $this->executeQuery('GET', $uri);
    }

    /**
     * transform qQuery string to SparQL fit for uri
     *
     * @param  string $query
     * @return string
     */
    public static function transformQuery($query)
    {
        $query = str_replace('%23', '#', $query);
        $query = urlencode($query);
        $query = str_replace('+', '%20', $query);

        return $query;
    }

    /**
     *
     * Perform the query to the SPARQL endpoint
     *
     * @param  string $verb    HTTP verb GET / POST
     * @param  string $query
     * @param  array  $options
     * @return Psr\Http\Message\StreamInterface
     */
    private function executeQuery($verb, $query, $options = [])
    {
        $options['auth'] = [$this->username, $this->password, 'digest'];
        $options['Content-Type'] = 'application/sparql-query';
        // try {
        $response = $this->guzzleClient->request($verb, $query, $options);
        $this->lastResponseCode = $response->getStatusCode();

        return $this->handleResponse($response);
    }

    /**
     * Getter lastResponseCode
     * @return int
     */
    public function getLastResponceCode()
    {
        return $this->lastResponseCode;
    }

    /**
     * Handle response of request
     *
     * Check errors based on response status code
     * Return the body of the request
     *
     * @param  Response $response
     * @return Psr\Http\Message\StreamInterface
     */
    private function handleResponse(Response $response)
    {
        // Virtuoso documentation states it only returns 200, 400, 500 status codes
        // but apparently they mean 2xx, 4xx and 5xx
        if ($response->getReasonPhrase() != 'OK' && $this->lastResponseCode > 299) {
            $message = "Something went wrong while executing query.";
            throw new \Exception($message);
        }

        if ($this->lastResponseCode >= '400' && $this->lastResponseCode <= '500') {
            $message = "The SPARQL endpoint returned an error.";
            throw new \Exception($message);
        }

        if ($this->lastResponseCode == '500') {
            $message = "The SPARQL endpoint returned a 500 error.";
            throw new \Exception($message);
        }

        return $response->getBody();
    }
}
