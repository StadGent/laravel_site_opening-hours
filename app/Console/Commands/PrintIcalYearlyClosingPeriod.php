<?php

namespace App\Console\Commands;

use DateInterval;
use Illuminate\Console\Command;
use Monolog\DateTimeImmutable;

/**
 * Artisan command to generate an ical dump for yearly recurring exceptions for
 * a specific time period.
 *
 * The {name} is required since we generate recurring events by creating VEVENT
 * for each year and keeping the same description for it, will make it appear
 * in openinghours as a recurring event... as of end 2025 we do not support
 *  DURATION:P7D
 *  RRULE:FREQ=YEARLY
 *
 * This command will then generate the ical `VEVENT` list for each year, starting
 * with the current year up to and including the year 2100, because we've
 * learned from the Y2K stuff, that about a 100 years is _more_ than enough room
 * to cater for all the possibilities, and _no way_ this site will still be up
 * and running in 2100.
 * -- Famous Last Words
 *
 * So every year there will be a period, defined by {from}-{to} that can be used
 * to add an exception to the openinghours for that period.
 */
class PrintIcalYearlyClosingPeriod extends Command
{
    protected const DATE_FORMAT_ICAL = "Ymd";
    protected const TIMESTAMP_FORMAT = "YmdHis";
    protected const MAX_YEAR = 2100;

    /**
     * The name and signature of the console command.
     * {name} {from} {to} defines the period to add an exception,
     * which will return yearly until (and including) 2100.
     *
     * @var string
     */
    protected $signature = 'openinghours:print-ical-yearly-closing-period
                            {name : The name of the closing period}
                            {from : The date to start from (YYYY-MM-DD or YYYYMMDD)}
                            {to : The end date of the period (must be same format as from)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Output ical VEVENT's for yearly recurring closing period.";

    /**
     * Execute the console command.
     *
     * @return int Exit status of the command
     */
    public function handle() : int
    {
        $result = $this->parseArgs($this->argument("from"), $this->argument("to"));
        $error = $result[0];
        if ($error) {
            $this->error($error);
            return Command::FAILURE;
        }
        /** @var \DateTimeImmutable */
        $from = $result[1];
        /** @var \DateTimeImmutable */
        $to = $result[2];
        $name = $this->argument("name");
        $this->printIcalEvents($name, $from, $to);
        return Command::SUCCESS;
    }

    /**
     * Print ical part of the closing period, defined by a starting- and end date.
     * It'll generate all the ical entries necessary from the start date up to
     * (and including) the year 2100.
     *
     * @param \DateTimeImmutable $from The date from which to start this period
     * @param \DateTimeImmutable $to   The ending date of the period
     */
    protected function printIcalEvents(string $name, \DateTimeImmutable $from, \DateTimeImmutable $to) : void
    {
        $timestamp = date("Ymd\THis\Z");
        while ($from->format("Y") <= self::MAX_YEAR) {
            echo "BEGIN:VEVENT\n";
            echo "DTSTAMP:$timestamp\n";
            echo "UID:{$this->uuid()}\n";
            echo "DTSTART;VALUE=DATE:{$from->format(self::DATE_FORMAT_ICAL)}\n";
            echo "DTEND;VALUE=DATE:{$to->format(self::DATE_FORMAT_ICAL)}", "\n";
            echo "SUMMARY;LANGUAGE=nl-BE:{$name}\n";
            echo "END:VEVENT\n";
            $from = $from->add(new DateInterval("P1Y"));
            $to = $to->add(new DateInterval("P1Y"));
        }
    }

    /**
     * Parse the given command line arguments {from} and {to}
     *
     * @param $from string The from date
     * @param $to string The to date
     *
     * @return array<string, DateTimeImmutable, DateTimeImmutable>
     *  Result of the arg parse, first element is an error message.
     *  If the error is `null`, there were no parse errors.
     *  Next two elements are the from date and to date.
     *  These will be `null` if there is an error.
     */
    protected function parseArgs(string $from, string $to) : array
    {
        $errAllowedFormats = "(YYYY-MM-DD or YYYYMMDD)";
        $dashCount = substr_count($from, "-");
        if ($dashCount != substr_count($to, "-")) {
            return ["From- and To date should be in the same format $errAllowedFormats", null, null];
        }
        if ($dashCount != 2 && $dashCount !=0) {
            return ["Only $errAllowedFormats are considered valid date formats", null, null];
        }
        $expectedLen = $dashCount == 2 ? 10 : 8;
        if (strlen($from) != $expectedLen) {
            $longShort = strlen($from) < $expectedLen ? "short" : "long";
            return ["Only $errAllowedFormats format allowed, the from date is too " . $longShort, null, null];
        }
        if (strlen($to) != $expectedLen) {
            $longShort = strlen($to) < $expectedLen ? "short" : "long";
            return ["Only $errAllowedFormats format allowed, the to date is too " . $longShort, null, null];
        }
        $dateFrom = null;
        $dateTo = null;
        $dti = new \DateTimeImmutable();
        $format = $dashCount == 2 ? "Y-m-d" : "Ymd";
        $dateFrom = $dti->createFromFormat($format, $from);
        if (!$dateFrom) {
            return ["Invalid from date", null, null];
        }
        $dateTo = $dti->createFromFormat($format, $to);
        if (!$dateTo) {
            return ["Invalid to date", null, null];
        }
        if ($dateFrom >= $dateTo) {
            return ["The from date must be earlier than the to date", null, null];
        }
        if ($dateFrom->format("Y") >= self::MAX_YEAR || $dateTo->format("Y") >= self::MAX_YEAR) {
            return ["This command does not handle dates beyond " . self::MAX_YEAR, null, null];
        }
        return [null, $dateFrom, $dateTo];
    }

    /**
     * Generate a (pseudo) UUID
     * This might not be a _real_ UUID, but for the purposes, it should be
     * GoodEnough™
     *
     * @return string The pseudo UUID
     */
    protected function uuid() : string
    {
        return substr(sha1(date(self::TIMESTAMP_FORMAT).microtime()), 0, 8) . '-'
            . substr(sha1(date(self::TIMESTAMP_FORMAT).microtime()), 0, 4) . '-'
            . substr(sha1(date(self::TIMESTAMP_FORMAT).microtime()), 0, 4) . '-'
            . substr(sha1(date(self::TIMESTAMP_FORMAT).microtime()), 0, 4) . '-'
            . substr(sha1(date(self::TIMESTAMP_FORMAT).microtime()), 0, 12);

    }
}
