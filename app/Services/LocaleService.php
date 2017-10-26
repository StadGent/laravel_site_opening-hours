<?php

namespace App\Services;

use App\Models\Channel;
use Illuminate\Http\Request;

/**
 * LocaleService to get the main locale out of the http request header
 * and return the correct format for date time
 */
class LocaleService
{
    /**
     * Singleton class instance.
     *
     * @var ChannelService
     */
    private static $instance;

    /**
     * @var string
     */
    private $requestLocale = false;

    /**
     * format from config locale_date_time_formats
     * with corresponding locale
     * ['date' => dateFormat, 'time' => timeFormat]
     *
     * @var array
     */
    private $format = '';

    /**
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $httpAcceptLang = $request->server('HTTP_ACCEPT_LANGUAGE');
        $this->requestLocale = $this->validateRequestLocale($httpAcceptLang);
        \App::setLocale(substr($this->requestLocale, 0, 2));
        $this->format = $this->fetchTheFormat($this->requestLocale);
    }

    /**
     * @return string locale
     */
    public function validateRequestLocale($httpAcceptLang)
    {
        $requestLocale = \Locale::acceptFromHttp($httpAcceptLang);
        $localeLocations = array_keys(config('app.locale_date_time_formats'));
        foreach ($localeLocations as $locale) {
            if ($locale === $requestLocale || substr($locale, 0, 2) === $requestLocale) {
                return $locale;
            }
        }

        return config('app.fallback_locale_location');
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
     * Get the date time format of a locale
     *
     * Default no locale is given as argument
     * and the locale will be taken from the HTTP accept language
     *
     * @param string/null $locale
     * @return string dateTime format
     */
    public function getDateFormat($locale = null)
    {
        if (!$locale) {
            return $this->format['date'];
        }

        return $this->fetchTheFormat($locale)['date'];
    }

    /**
     * Get the date time format of a locale
     *
     * Default no locale is given as argument
     * and the locale will be taken from the HTTP accept language
     *
     * @param string/null $locale
     * @return string dateTime format
     */
    public function getTimeFormat($locale = null)
    {
        if (!$locale) {
            return $this->format['time'];
        }

        return $this->fetchTheFormat($locale)['time'];
    }

    /**
     * @param $locale
     * @return array
     */
    private function fetchTheFormat($locale)
    {
        $formats = config('app.locale_date_time_formats');
        if (!$locale || !array_key_exists($locale, $formats)) {
            $locale = $this->requestLocale;
        }

        return $formats[$locale];
    }
}
