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
     * HttpClient to be fixed with endpoint to send requests
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
     * Default null
     * After correct request: is set with statuscode
     * @var mixed
     */
    private $lastResponseCode = null;

    /**
     * Singleton class instance.
     *
     * @var SparqlService
     */
    private static $instance;

    /**
     *
     * Private contructor for Singleton pattern
     * force set client with default values from .env file
     *
     * @return SparqlService
     */
    private function __construct()
    {
        $this->setClient();
    }

    /**
     * GetInstance for Singleton pattern
     *
     * @return SparqlService
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new SparqlService();
        }

        return self::$instance;
    }

    /**
     * Create new Guzzle client with fixed base_uri endpoint
     *
     * Is public as perhaps other endpoints can be needed in logic
     * As currently digest authenitcation is only supported when using the cURL handler
     * http://docs.guzzlephp.org/en/stable/request-options.html#auth
     *
     * The base baseConnectionTest is triggered here to check or Client is correctly set
     * When not correct, the thrown error will prevent any further logic being send to the object
     *
     * @param $endpoint
     */
    public function setClient($endpoint = null, $username = null, $password = null, $defaultGraph = null)
    {

        $this->username = $username ?: env('SPARQL_WRITE_ENDPOINT_USERNAME');
        $this->password = $password ?: env('SPARQL_WRITE_ENDPOINT_PASSWORD');
        $this->defaultGraph = $defaultGraph ?: env('SPARQL_WRITE_GRAPH');

        $handler = new CurlHandler();
        $options['handler'] = HandlerStack::create($handler); // Wrap w/ middleware
        $options['base_uri'] = $endpoint ?: env('SPARQL_WRITE_ENDPOINT');
        $this->guzzleClient = new Client($options);

        $this->baseConnectionTest();
    }

    /**
     * Test if the current Guzzle client contains the Sparql protocol
     *
     * A base ASK query request is fired to the current client.
     * Throws error when
     * - no connection to given endpoint is possible
     * - endpoint returns a statuscode that is not 200
     * - endpoint returns a value that is not parsable as json
     * - endpoint returns not the expected boolean as answer on the ASK query
     *
     * @param  array  $options      Add other Guzzle Request options
     * @return void
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

        if ($data !== true && $data !== false) {
            throw new \Exception("No correct data came back in connection test", 1);
        }

    }

    /**
     * Execute the query
     *
     * Return the response coming from the result of a SPARQL query
     * If the method was not a GET method, return a boolean indicating
     * if the query was performed succesful or not
     *
     * @deprecated
     * @param  string $query
     * @param  string $method   HTTP verb GET / POST
     * @param  mixed $format    Default null will be handled later on as json
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
     * Reset of $this->lastResponseCode
     * Assemble the query for POST syntax
     * As graph-uri value, the defaultGraph is set
     * Set the format default to json for any conformation reply
     * Query is send as ['form_params']['query'] = $query (no uri transform is needed here)
     *
     * @param $query
     * @return mixed
     */
    public function post($query, $format = null)
    {
        $this->lastResponseCode = null;
        $uri = '?format=' . ($format ?: 'json');
        $options = ['body' => $query];

        $options['headers']['Content-Type'] = 'application/sparql-update';
        return $this->executeQuery('POST', $uri, $options);
    }

    /**
     * Prepare and perform a GET to the SparQL
     *
     * Reset of $this->lastResponseCode
     * Assemble the uri transformed query for GET syntax
     * Set the format default to json for any conformation reply
     *
     * @param $query
     * @param $format
     * @return mixed
     */
    public function get($query, $format = null)
    {
        $this->lastResponseCode = null;
        $uri = '?query=' . static::transformQuery($query) . '&format=' . ($format ?: 'json');
        $options['headers']['Content-Type'] = 'application/sparql-query';
        return $this->executeQuery('GET', $uri, $options);
    }

    /**
     * Transform query string to SparQL fit for uri
     *
     * @todo perhaps look for a nicer syntax sugar (but it works as is)
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
     * Perform the query to the SPARQL endpoint
     *
     * Standard use auth. This is not needed for the GET SELECT,
     * but to make extra logic for a "less safe exception" is ... not logic
     *
     * 'application/sparql-query' is set as Content-type
     *
     * @param  string $verb    HTTP verb GET / POST
     * @param  string $query
     * @param  array  $options
     * @return Psr\Http\Message\StreamInterface
     */
    private function executeQuery($verb, $query, $options = [])
    {
        $options['auth'] = [$this->username, $this->password, 'digest'];
        $response = $this->guzzleClient->request($verb, $query, $options);

        return $this->handleResponse($response);
    }

    /**
     * Getter lastResponseCode
     * @return int/null
     */
    public function getLastResponceCode()
    {
        return $this->lastResponseCode;
    }

    /**
     * Handle response of request
     *
     * Set status code into property
     * Check errors based on response status code
     * Return the body of the request
     *
     * @param  Response $response
     * @return Psr\Http\Message\StreamInterface
     */
    private function handleResponse(Response $response)
    {
        $this->lastResponseCode = $response->getStatusCode();
        // Virtuoso documentation states it only returns 200, 400, 500 status codes
        // but apparently they mean 2xx, 4xx and 5xx
        if ($response->getReasonPhrase() !== 'OK' && $this->lastResponseCode > 299) {
            $message = "Something went wrong while executing query.";
            throw new \Exception($message);
        }

        if ($this->lastResponseCode >= 400 && $this->lastResponseCode <= 499) {
            $message = "The SPARQL endpoint returned an error.";
            throw new \Exception($message);
        }

        if ($this->lastResponseCode >= 500) {
            $message = "The SPARQL endpoint returned a 500 error.";
            throw new \Exception($message);
        }

        return $response->getBody();
    }
}
