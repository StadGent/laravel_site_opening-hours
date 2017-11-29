<?php

namespace Tests\Transformers;

use App\Http\Transformers\ChannelTransformer;
use App\Models\Channel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ChannelTransformerTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * @var ChannelTransformer
     */
    private $transformer;

    /**
     * @var Channel
     */
    private $channel;

    const DATETIME_REPLACEMENT = "/[0-9]{4}(-[0-9]{2}){2}T[0-9]{2}(:[0-9]{2}){2}\+[0-9]{2}:[0-9]{2}/";

    public function setup()
    {
        parent::setup();

        $this->channel = \App\Models\Channel::first();
        $this->transformer = new ChannelTransformer();
    }

    /**
     * @test
     * @group content
     */
    public function testTransformJsonItem()
    {
        $actual = $this->transformer->transformJsonItem($this->channel);
        $content = file_get_contents(__DIR__ . '/../data/transformers/channel/transformJsonItem.json');
        $expected = json_encode(json_decode($content,true));
        $actual = preg_replace(self::DATETIME_REPLACEMENT, "", $actual);
        $actual = preg_replace(self::DATETIME_REPLACEMENT, "", $actual);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     * @group content
     */
    public function testTransformJsonCollection()
    {
        $actual = $this->transformer->transformJsonCollection((new Collection())->add($this->channel));
        $content = file_get_contents(__DIR__ . '/../data/transformers/channel/transformJsonCollection.json');
        $expected = json_encode(json_decode($content,true));
        $actual = preg_replace(self::DATETIME_REPLACEMENT, "", $actual);
        $actual = preg_replace(self::DATETIME_REPLACEMENT, "", $actual);
        $this->assertEquals($expected, $actual);
    }
}
