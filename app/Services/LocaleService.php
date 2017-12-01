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
     * @var LocaleService
     */
    private static $instance;

    /**
     * @var string|bool
     */
    private $activeLocale = false;

    /**
     * format from config locale_date_time_formats
     * with corresponding locale
     * ['date' => dateFormat, 'time' => timeFormat]
     *
     * @var array
     */
    private $format = [];

    /**
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $httpAcceptLang = $request->server('HTTP_ACCEPT_LANGUAGE');
        $requestLocale = \Locale::acceptFromHttp($httpAcceptLang);
        $this->setLocale($requestLocale);
    }

    /**
     * Set locale for service and App
     *
     * input will be validated
     * will be set for app
     * will trigger the fetchTheFormat
     *
     * @param $locale
     */
    public function setLocale($locale)
    {
        $locale = str_replace('-', '_', $locale);
        $this->activeLocale = $this->validateLocale($locale);
        \App::setLocale(substr($this->activeLocale, 0, 2));
        $this->format = $this->fetchTheFormat($this->activeLocale);
    }

    /**
     * Validate suggested locale and returns format to be set in $this->activeLocale
     *
     * accept formats xx  or xx_XX
     * example nl or nl_BE
     *
     * @return string locale
     */
    public function validateLocale($suggestLocale)
    {
        $localeLocations = array_keys(config('app.locale_date_time_formats'));
        foreach ($localeLocations as $locale) {
            if ($locale === $suggestLocale || substr($locale, 0, 2) === $suggestLocale) {
                return $locale;
            }
        }

        return config('app.fallback_locale_location');
    }
    
    /**
     * Private contructor for Singleton pattern
     */
    private function __construct()
    {
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
     * @param string /null $locale
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
     * @param string /null $locale
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
     * Get week start day based on the locale
     *
     * When no locale is given, the active locale is used
     * IntlCalendar->getFirstDayOfWeek() -1 will be returned as value
     *
     * @param $locale
     * @return integer
     */
    public function getWeekStartDay($locale = null)
    {
        $formats = config('app.locale_date_time_formats');
        if (!$locale || !array_key_exists($locale, $formats)) {
            $locale = $this->activeLocale;
        }
        $cal = \IntlCalendar::createInstance(null, $locale);

        return ($cal->getFirstDayOfWeek() - 1);
    }

    /**
     * Get week end day based on the locale
     *
     * Collect the $this->getWeekStartDay value
     * add 6 day to get to the end of the week
     * when result > 6 remove 7 days to get to correct CARBON::"day" value
     *
     * @param $locale
     * @return integer
     */
    public function getWeekEndDay($locale = null)
    {
        $result = $this->getWeekStartDay($locale) + 6;

        return ($result <= 6 ? $result : $result - 7);
    }

    /**
     * Set the formats based on the locale
     *
     * @param $locale
     * @return array
     */
    private function fetchTheFormat($locale)
    {
        $formats = config('app.locale_date_time_formats');
        if (!$locale || !array_key_exists($locale, $formats)) {
            $locale = $this->activeLocale;
        }

        return $formats[$locale];
    }

    /**
     * @param $timeFormat
     */
    public function setTimeFormat($timeFormat)
    {
        $this->format['time'] = $timeFormat;
    }

    /**
     * @param $dateFormat
     */
    public function setDateFormat($dateFormat)
    {
        $this->format['date'] = $dateFormat;
    }
}
