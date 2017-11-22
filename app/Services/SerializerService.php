<?php


namespace App\Services;


use App\Http\Transformers\TransformerInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
//use Illuminate\Http\Request;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

/**
 * Class SerializerService
 * @package App\Services
 */
class SerializerService
{

    private $request;
    private static $instance;
    const MIME_TYPES = ['application/json', 'text/html', 'application/ld+json', 'text/plain'];

    public function setRequest(Request $request)
    {
        $this->request = app(Request::class);
    }

    /**
     * GetInstance for Singleton pattern
     *
     * @return ChannelService
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Use the formatter to transform an item into the right format
     *
     * @param TransformerInterface $transformer
     * @param Model $model
     * @return mixed
     */
    public function transformItem(TransformerInterface $transformer, Model $model)
    {
        $format = $this->getBestSupportedMimeType();

        $supportedFormats = $transformer::getSupportedFormats();
        if (!isset($supportedFormats['item'][$format])) {
            throw new NotAcceptableHttpException();
        }

        $function = $supportedFormats['item'][$format];

        return $transformer->{$function}($model);
    }

    /**
     *  * Use the formatter to transform a collection into the right format
     *
     * @param TransformerInterface $transformer
     * @param Collection $collection
     * @return mixed
     */
    public function transformCollection(TransformerInterface $transformer, Collection $collection)
    {
        $format = $this->getBestSupportedMimeType();

        $supportedFormats = $transformer::getSupportedFormats();
        if (!isset($supportedFormats['collection'][$format])) {
            throw new NotAcceptableHttpException();
        }

        $function = $supportedFormats['collection'][$format];

        return $transformer->{$function}($collection);
    }

    /**
     * Get the current mime type based on the current request
     *
     * Credit for snippit: Maciej Łebkowski
     * https://stackoverflow.com/a/1087498/4174548
     *
     * @param $mimeTypes
     * @return null
     */
    public function getBestSupportedMimeType()
    {
        // Values will be stored in this array
        $acceptTypes = [];

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
            $acceptTypes[$a] = $q;
        }
        arsort($acceptTypes);

        foreach ($acceptTypes as $mime => $q) {
            if ($q && in_array($mime, self::MIME_TYPES, true)) {
                return $mime;
            }
        }
        // no mime-type found

        return;
    }

}