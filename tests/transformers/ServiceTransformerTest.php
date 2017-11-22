<?php

namespace Tests\Transformers;

use App\Http\Transformers\OpeninghoursTransformer;
use App\Http\Transformers\ServiceTransformer;
use App\Models\Service;
use App\Services\LocaleService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ServiceTransformerTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * @var ServiceTransformer
     */
    private $transformer;

    /**
     * @var Service
     */
    private $service;

    public function setup()
    {
        parent::setup();

        $this->service = \App\Models\Service::first();
        $this->transformer = new ServiceTransformer();
    }

    /**
     * @test
     * @group content
     */
    public function testTransformJsonItem()
    {
        $actual = $this->transformer->transformJsonItem($this->service);
        $content = file_get_contents(__DIR__ . '/../data/transformers/service/transformJsonItem.json');
        $expected = json_encode(json_decode($content,true));
        $actual = preg_replace("/[0-9]{4}(-[0-9]{2}){2}T[0-9]{2}(:[0-9]{2}){2}Z/", "", $actual);
        $actual = preg_replace("/\"description\":\"[\w\s.]*\"/", "\"description\":\"\"", $actual);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     * @group content
     */
    public function testTransformJsonCollection()
    {
        $actual = $this->transformer->transformJsonCollection((new Collection())->add($this->service));
        $content = file_get_contents(__DIR__ . '/../data/transformers/service/transformJsonCollection.json');
        $expected = json_encode(json_decode($content,true));
        $actual = preg_replace("/[0-9]{4}(-[0-9]{2}){2}T[0-9]{2}(:[0-9]{2}){2}Z/", "", $actual);
        $actual = preg_replace("/\"description\":\"[\w\s.]*\"/", "\"description\":\"\"", $actual);
        $this->assertEquals($expected, $actual);
    }
}
