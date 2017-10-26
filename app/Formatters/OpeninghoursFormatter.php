<?php

namespace App\Formatters;

use App\Formatters\Openinghours\BaseFormatter;
use App\Http\Requests\GetQueryRequest;
use App\Models\Service;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

/**
 * Formatter class for Openinghours
 * renders given data into given format
 */
class OpeninghoursFormatter implements EndPointFormatterInterface
{
    /**
     * contains the uri of the active record service
     * @var string
     */
    private $service;

    /**
     * @var array
     */
    private $formatters = [];

    /**
     * @var mixed
     */
    private $request;

    /**
     * Adds format to endpointformatter
     *
     * Checksor format can be found in the correct namespace
     *
     * @param $formatter
     * @return $this
     */
    public function addFormat($formatter)
    {
        if (!($formatter instanceof BaseFormatter)) {
            throw new \Exception($formatter . " is not supported as format for " . self::class, 1);
        }
        $this->formatters[$formatter->getSupportFormat()] = $formatter;

        return $this;
    }

    /**
     * Return all supported formats of this endpoint
     *
     * @return array
     */
    public function getFormatters()
    {
        return $this->formatters;
    }

    /**
     * @param Service $service
     */
    public function setService(Service $service)
    {
        $this->service = $service;
    }

    /**
     * @param Request $request
     */
    public function setRequest(GetQueryRequest $request)
    {
        $this->request = $request;
    }

    /**
     * Render data according to the given format
     *
     * @param  string $format to match with available formats
     * @param  array $data   data to transform
     * @return mixed         formatted data
     */
    public function render($data)
    {
        if (!$data) {
            throw new \Exception("No data given for formatter" . self::class, 1);
        }

        $activeFormatter = null;

        $formats = [];
        $prefered = $this->getBestSupportedMimeType(array_keys($this->formatters, null, true));
        foreach ($prefered as $format => $weight) {
            if (isset($this->formatters[$format]) && $weight !== 0) {
                $this->formatters[$format]->setRequest($this->request);

                return $this->formatters[$format]->render($data)->getOutput();
            }
        }
        throw new NotAcceptableHttpException();
    }

    /**
     * Credit for snippit: Maciej Łebkowski
     * https://stackoverflow.com/a/1087498/4174548
     *
     * it is good enough as we use it, althou it does not support wildcards:
     *  " *_/_*" or "*_/json "or "application/_*"
     * (extra underscores used as not to interrupt my comment)
     *
     * @param $mimeTypes
     * @return null
     */
    public function getBestSupportedMimeType($mimeTypes)
    {
        // Values will be stored in this array
        $AcceptTypes = [];

        if (!$this->request) {
            throw new \Exception("Error Processing Request as in absence of a request", 1);
        }

        // Accept header is case insensitive, and whitespace isn’t important
        $accept = strtolower(str_replace(' ', '', $this->request->headers->get('Accept')));
        // divide it into parts in the place of a ","
        $accept = explode(',', $accept);
        foreach ($accept as $a) {
            // the default quality is 1.
            $q = 1;
            // check if there is a different quality
            if (strpos($a, ';q=')) {
                // divide "mime/type;q=X" into two parts: "mime/type" i "X"
                list($a, $q) = explode(';q=', $a);
            }
            // mime-type $a is accepted with the quality $q
            // WARNING: $q == 0 means, that mime-type isn’t supported!
            $AcceptTypes[$a] = $q;
        }
        arsort($AcceptTypes);

        // if no parameter was passed, just return parsed data
        if (!$mimeTypes) {
            return $AcceptTypes;
        }

        $mimeTypes = array_map('strtolower', (array) $mimeTypes);

        // let’s check our supported types:
        foreach ($AcceptTypes as $mime => $q) {
            if ($q && in_array($mime, $mimeTypes, true)) {
                return $mime;
            }
        }
        // no mime-type found

        return;
    }
}
