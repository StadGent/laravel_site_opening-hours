<?php

namespace Tests\Formatters;

use Illuminate\Foundation\Testing\DatabaseTransactions;

class OpeninghoursFormatterTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * @var App\Formatters\Openinghours
     */
    private $formatter;

    /**
     * @var array
     */
    private $data = [];

    public function setup()
    {
        parent::setup();

        $this->formatter = app('OpeninghoursFormatter');

        $service = \App\Models\Service::first();
        $this->serviceuri = $service->uri;
        $this->channelKeys = $service->channels()->pluck('label')->all();
        foreach ($this->channelKeys as $key) {
            $this->data[$key] = ['08:00', '17:00'];
        }
    }

    /**
     * @test
     * @group validation
     */
    public function testAddUnknownFormatThrowsError()
    {
        $this->setExpectedException('Exception', 'NotAFormatter is not supported as format for App\Formatters\OpeninghoursFormatter');
        $this->formatter->addFormat('NotAFormatter');
    }

    /**
     * @test
     * @group validation
     */
    public function testNoDataThrowsError()
    {
        $this->setExpectedException('Exception', 'No data given for formatterApp\Formatters\OpeninghoursFormatter');
        $output = $this->formatter->render('json', []);
    }

    /**
     * @test
     * @group validation
     */
    public function testRequestUnknownFormatThrowsError()
    {
        $this->setExpectedException('Exception', 'The given format NotAFormatter is not available in App\Formatters\OpeninghoursFormatter');
        $output = $this->formatter->render('NotAFormatter', ['thisIsData' => true]);
    }

    /**
     * @test
     * @group content
     */
    public function testFormatJsonJustReturnsOriginalData()
    {
        $output = $this->formatter->render('json', $this->data);

        $this->assertEquals($this->data, $output);
    }

    /**
     * @test
     * @group content
     * @todo check content when structure is known
     */
    public function testFormatJsonLdEhNotSureYet()
    {
        $this->formatter->serviceUri = $this->serviceuri;
        $output = $this->formatter->render('json-ld', $this->data);
        // if no errors => all is good... I hope
    }

    /**
     * @test
     * @group content
     * @todo check content when structure is known
     */
    public function testFormatHtmlGivesHtml()
    {
        $output = $this->formatter->render('html', $this->data);
        $result = '<div>';
        foreach ($this->channelKeys as $key) {
            $result .= '<h4>' . $key . '</h4><div>08:00</div><div>17:00</div>';
        }
        $result .= '</div>';
        $this->assertEquals($result, $output);
    }

    /**
     * @test
     * @group content
     * @todo check content when structure is known
     */
    public function testFormatTextGivesAString()
    {
        $output = $this->formatter->render('text', $this->data);

        $result = [];
        foreach ($this->channelKeys as $key) {
            $result[] = $key . ': ' . PHP_EOL . '01-01-1970 08:00' . PHP_EOL . '01-01-1970 17:00';
        }

        $this->assertEquals(implode(PHP_EOL . PHP_EOL . PHP_EOL, $result), $output);
    }

}
