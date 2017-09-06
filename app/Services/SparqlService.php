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
     * @param  string $query
     * @param  string $method
     * @param  string $format
     * @return mixed
     */
    public function performSparqlQuery($query, $method = 'GET', $format = 'turtle')
    {
        try {
            $data = $this->executeQuery($query, $method, $format);

            if ($method == 'GET') {
                return $data;
            }

            return true;
        } catch (\Exception $ex) {
            \Log::error('Something went wrong while writing to the LOD repository: ' . $ex->getMessage());
        }

        return false;
    }

    /**
     * Prepare the query statement to be passed in a cURL requests
     * meaning we need to apply proper url encoding and take into
     * account specific characters
     *
     * @param  string $query
     * @return string
     */
    private function prepareQuery($query)
    {
        $query = str_replace('%23', '#', $query);
        $query = urlencode($query);

        return str_replace('+', '%20', $query);
    }

    /**
     * Perform the query to the SPARQL endpoint which is
     * based on the configured properties (endpoint, username, pw) and the
     * pass query and request method, then return the result.
     *
     * TODO: abstract the sparql service more to allow for specific endpoints
     * to be configured upon constructing the class for specific methods
     *
     * @param  string $query
     * @param  string $method
     * @param  string $format Default format is turtle
     * @return string
     */
    private function executeQuery($query, $method = 'GET', $format = 'turtle')
    {
        $curl = curl_init();

        if ($method == 'GET' && empty($format)) {
            $format = 'turtle';
        } elseif ($method != 'GET') {
            $format = '';
        }

        // POST queries are sent to the graph-crud-auth API endpoint
        if ($method == 'POST') {
            // The crud endpoint works with digest authentication
            if (! empty($this->username)) {
                curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
                curl_setopt($curl, CURLOPT_USERPWD, $this->username . ':' . $this->password);
            }

            $uri = env('SPARQL_WRITE_INSERT_ENDPOINT') . '?graph-uri=' . $this->defaultGraph;

            curl_setopt($curl, CURLOPT_URL, $uri);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                                            'Content-Type: application/sparql-query'
                                            ));
            curl_setopt($curl, CURLOPT_POSTFIELDS, $query);

            // Request for a string result instead of having the result being outputted
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        } else {
            // If credentials are set, put the HTTP auth header in the cURL request
            if (! empty($this->username)) {
                curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
                curl_setopt($curl, CURLOPT_USERPWD, $this->username . ':' . $this->password);
            }

            $uri = $this->makeGetRequestUri($this->prepareQuery($query), $format);

            // Make and set the request URI
            curl_setopt($curl, CURLOPT_URL, $uri);

            // Request for a string result instead of having the result being outputted
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        }

        // Execute the request
        $response = curl_exec($curl);
        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // Virtuoso documentation states it only returns 200, 400, 500 status codes
        // but apparently they mean 2xx, 4xx and 5xx
        if (! $response && $responseCode > 299) {
            $curl_err = curl_error($curl);

            $uri = urldecode($uri);

            $message = "Something went wrong while executing query. The request we put together was: $uri. The response code was: $responseCode. The response was: $response";

            throw new \Exception($message);
        }

        if ($responseCode >= '400' && $responseCode <= '500') {
            $uri = urldecode($uri);

            $message = "The SPARQL endpoint returned an error. If the SPARQL query contained a parameter, don't forget to pass them as a query string parameter. The error was: $response. The URI was: $uri";

            throw new \Exception($message);
        } elseif ($responseCode == '500') {
            $uri = urldecode($uri);

            $message = "The SPARQL endpoint returned a 500 error. If the SPARQL query contained a parameter, don't forget to pass them as a query string parameter. The URI was: $uri";

            throw new \Exception($message);
        }

        curl_close($curl);

        return $response;
    }

    /**
     * Make and return the request URI based on
     * the passed query and the configured endpoint
     *
     * @param  string $query
     * @param  string $format
     * @return string
     */
    private function makeGetRequestUri($query, $format = null)
    {
        $requestUri = $this->endpoint . '?query=' . $query;

        if (! empty($format)) {
            $requestUri .= '&format=' . $format;
        }

        return $requestUri;
    }

    /**
     * Make and return the request URI based on
     * the passed query and the configured endpoint
     *
     * @param  string $query
     * @param  string $format
     * @return string
     */
    private function makePostRequestUri($query, $format = null)
    {
        $requestUri = $this->endpoint . '?query=' . $query;

        if (! empty($format)) {
            $requestUri .= '&format=' . $format;
        }

        return $requestUri;
    }
}
