<?php

namespace App\Console\Commands;

use DateInterval;
use Illuminate\Console\Command;
use Monolog\DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * Artisan command to generate an ical dump for yearly recurring exceptions for
 * a specific time period.
 *
 * Since we don't support:
 *  DURATION:P7D
 *  RRULE:FREQ=YEARLY
 * yet, the {name} is necessary to present our closing period as a yearly
 * recurring event.
 *
 * This command will generate ical `VEVENT` entries for each year, starting
 * with the current year up to and including the year 2100, because we've
 * learned from the Y2K stuff, that about a 100 years is _more_ than enough
 * room to cater for all the possibilities, and _no way_ this site will still
 * be up and running in 2100.
 *
 * So in the UI there will be a recurring exception that will repeat every year up to 2100...
 */
class PrintIcalYearlyClosingPeriod extends Command
{
    protected const DATE_FORMAT_ICAL = "Ymd";
    protected const TIMESTAMP_FORMAT = "YmdHis";
    protected const MAX_YEAR = 2100;

    /**
     * The signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openinghours:print-ical-yearly-closing-period
                            {name : The name of the closing period}
                            {from : The date to start from (YYYY-MM-DD or YYYYMMDD)}
                            {to : The end date of the period (must be same format as from)}';

    /**
     * The description of this command.
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
     * Print the VEVENT's for the closing period.
     * Generate all the ical entries necessary from the start date up to (and
     * including) the year 2100.
     *
     * @param string $name The name (label) that must be given to this recurring event
     * @param \DateTimeImmutable $from The date from which to start this period
     * @param \DateTimeImmutable $to   The ending date of the period
     */
    protected function printIcalEvents(string $name, \DateTimeImmutable $from, \DateTimeImmutable $to) : void
    {
        $timestamp = date("Ymd\THis\Z");
        while ($from->format("Y") <= self::MAX_YEAR) {
            $this->output->writeln("BEGIN:VEVENT");
            $this->output->writeln("DTSTAMP:$timestamp");
            $this->output->writeln("UID:" . Uuid::uuid4());
            $this->output->writeln("DTSTART;VALUE=DATE:{$from->format(self::DATE_FORMAT_ICAL)}");
            $this->output->writeln("DTEND;VALUE=DATE:{$to->format(self::DATE_FORMAT_ICAL)}");
            $this->output->writeln("SUMMARY;LANGUAGE=nl-BE:{$name}");
            $this->output->writeln("END:VEVENT");
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
}
