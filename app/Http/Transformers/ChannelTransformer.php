<?php

namespace App\Http\Transformers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ChannelTransformer implements TransformerInterface
{

    const SUPPORTED_FORMATS = [
        'item' => [
            'application/json' => 'transformJsonItem'
        ],
        'collection' => [
            'application/json' => 'transformJsonCollection'
        ]
    ];

    /**
     * @return array
     */
    public static function getSupportedFormats()
    {
        return self::SUPPORTED_FORMATS;
    }

    /**
     * @param Model $channel
     * @return array
     */
    private function getItemData(Model $channel)
    {
        $lastVersion = $channel->openinghours->sortByDesc(
            fn($a, $b) => (new CarbonImmutable($a->end_date))->getTimestamp() - (new CarbonImmutable($b->end_date))->getTimestamp()
        )->first()?->end_date;
        return [
            'id' => $channel->id,
            'label' => $channel->label,
            'serviceId' => $channel->service_id,
            'createdAt' => $channel->created_at->format(DATE_ATOM),
            'updatedAt' => $channel->created_at->format(DATE_ATOM),
            'type' => $channel->type,
            'lastVersionExpiresOn' => $lastVersion ? (new CarbonImmutable($lastVersion))->format(DATE_ATOM) : null,
        ];
    }

    /**
     * @param Model $channel
     * @return string
     */
    public function transformJsonItem(Model $channel)
    {
        return json_encode($this->getItemData($channel));
    }

    /**
     * @param Collection $collection
     * @return string
     */
    public function transformJsonCollection(Collection $collection)
    {
        $dataCollection = [];

        foreach ($collection as $model) {
            $dataCollection[] = $this->getItemData($model);
        }

        return json_encode($dataCollection);
    }
}
