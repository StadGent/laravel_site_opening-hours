<?php


namespace App\Http\Transformers;


use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ServiceTransformer implements TransformerInterface
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
     * @param Model $service
     * @return array
     */
    public function getItemData(Model $service){
        return [
            'id' => $service->id,
            'uri' => $service->uri,
            'label' => $service->label,
            'description' => $service->description,
            'createdAt' => $service->created_at->format('Y-m-d\TH:i:s\Z'),
            'updatedAt' => $service->created_at->format('Y-m-d\TH:i:s\Z'),
            'sourceIdentifier' => $service->identifier,
            'source' => $service->source ? $service->source : '',
            'draft' => $service->draft == 1,
        ];
    }

    /**
     * @param Model $service
     * @return string
     */
    public function transformJsonItem(Model $service)
    {
        return json_encode($this->getItemData($service));
    }

    /**
     * @param Collection $collection
     * @return string
     */
    public function transformJsonCollection(Collection $collection)
    {
        $dataCollection = [];

        foreach ($collection as $service) {
            $dataCollection[] = $this->getItemData($service);
        }

        return json_encode($dataCollection);
    }

}