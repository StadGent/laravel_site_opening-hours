<?php

namespace App\Formatters;

abstract class Formatter
{

    private $data;
    /**
     * Render data according to the given format
     *
     * @param  string $format must match one of the keys in the OUTPUT_MAPPER
     * @param  array $data   [description]
     * @return mixed         [description]
     */
    abstract public function render($format, $data);

    /**
     * Adapter to transform data into json format
     * @return json formatted data
     */
    abstract protected function toJSON();

    /**
     * Adapter to transform data into json format
     * @return json-ld formatted data
     */
    abstract protected function toJSONLD();

    /**
     * Adapter to transform data into json format
     * @return string formatted data
     */
    abstract protected function toTEXT();

    /**
     * Adapter to transform data into json format
     * @return html formatted data
     */
    abstract protected function toHTML();

}
