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

    public function __construct($endpoint, $username = '', $password = '')
    {
        if (empty($endpoint)) {
            \Log::warning('No SPARQL endpoint was passed to the SparqlService.');
        }

        $this->endpoint = $endpoint;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Return the response coming from the result of a SPARQL query
     * If the method was not a GET method, return a boolean indicating
     * if the query was performed succesful or not
     *
     * @param  string $query
     * @param  string $method
     * @return mixed
     */
    public function performSparqlQuery($query, $method = 'GET')
    {
        try {
            $data = $this->executeQuery($this->prepareQuery($query), $method);

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
     * @param  string $query
     * @return string
     */
    private function executeQuery($query, $method = 'GET')
    {
        $curl = curl_init();

        // If credentials are set, put the HTTP auth header in the cURL request
        if (! empty($this->username)) {
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($curl, CURLOPT_USERPWD, $this->username . ':' . $this->password);
        }

        $format = '';

        if ($method == 'GET') {
            $format = 'turtle';
        }

        $uri = $this->makeRequestUri($query, $format);

        // Make and set the request URI
        curl_setopt($curl, CURLOPT_URL, $uri);

        // Request for a string result instead of having the result being outputted
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Execute the request
        $response = curl_exec($curl);

        if (! $response) {
            $curl_err = curl_error($curl);

            $uri = urldecode($uri);

            $message = "Something went wrong while executing query. The request we put together was: $uri.";

            throw new \Exception($message);
        }

        $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // According to the SPARQL 1.1 spec, a SPARQL endpoint can only return 200,400,500 responses
        if ($response_code == '400') {
            $uri = urldecode($uri);

            $message = "The SPARQL endpoint returned a 400 error. If the SPARQL query contained a parameter, don't forget to pass them as a query string parameter. The error was: $response. The URI was: $uri";

            throw new \Exception($message);
        } elseif ($response_code == '500') {
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
    private function makeRequestUri($query, $format = null)
    {
        $requestUri = $this->endpoint . '?query=' . $query;

        if (! empty($format)) {
            $requestUri .= '&format=' . $format;
        }

        return $requestUri;
    }
}
