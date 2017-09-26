<?php

namespace App\Services;

/**
 * This class is responsible to make requests to and return results from a SPARQL endpoint
 */
class SparqlService
{
    /**
     * The URI of the SPARQL endpoint
     * @var string
     */
    private $endpoint;

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
    public function __construct($endpoint, $username = '', $password = '', $defaultGraph = '')
    {
        if (empty($endpoint)) {
            \Log::warning('No SPARQL endpoint was passed to the SparqlService.');
        }

        $this->endpoint = $endpoint;
        $this->username = $username;
        $this->password = $password;

        if (empty($defaultGraph)) {
            $this->defaultGraph = env('SPARQL_WRITE_GRAPH');
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
                    return $this->post($query);
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
    public function post($query)
    {
        $curl = $this->initRequest();

        // The crud endpoint works with digest authentication
        if (!empty($this->username)) {
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/sparql-query',
        ]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $query);

        // format uri
        $uri = env('SPARQL_WRITE_INSERT_ENDPOINT') . '?graph-uri=' . $this->defaultGraph;

        $this->executeQuery($query, $uri);

        return $this->lastResponseCode == 200;
    }

    /**
     * Prepare and perform a GET to the SparQL
     *
     * @param $query
     * @param $format
     * @return mixed
     */
    public function get($query, $format = 'turtle')
    {
        $curl = $this->initRequest();
        // If credentials are set, put the HTTP auth header in the cURL request
        if (!empty($this->username)) {
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        }
        // format uri
        $query = str_replace('%23', '#', $query);
        $query = urlencode($query);
        $query = str_replace('+', '%20', $query);
        $uri = $this->endpoint . '?query=' . $query . '&format=' . $format;

        return $this->executeQuery($curl, $uri);
    }

    /**
     * Setup cURL handle
     * @return cURL hanlde
     */
    private function initRequest()
    {
        $curl = curl_init();
        $this->lastResponseCode = null;

        if (!empty($this->username)) {
            curl_setopt($curl, CURLOPT_USERPWD, $this->username . ':' . $this->password);
        }

        // Request for a string result instead of having the result being outputted
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        return $curl;

    }

    /**
     * Perform the query to the SPARQL endpoint which is
     * based on the configured properties (endpoint, username, pw) and the
     * pass query, then return the result.
     *
     * @param  string $query
     * @param  string $method
     * @param  string $format Default format is turtle
     * @return string
     */
    private function executeQuery($curl, $uri)
    {
        // Make and set the request URI
        curl_setopt($curl, CURLOPT_URL, $uri);
        // Execute the request
        $response = curl_exec($curl);
        $this->lastResponseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $this->handleResponseCode(!!$response);

        curl_close($curl);

        return $response;
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
     * [handleResponseCode description]
     * @param  bool   $notEmptyResponse 
     * @return void
     */
    private function handleResponseCode(bool $succesExec)
    {
        // Virtuoso documentation states it only returns 200, 400, 500 status codes
        // but apparently they mean 2xx, 4xx and 5xx
        if ($succesExec && $this->lastResponseCode > 299) {
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
    }
}
