<?php

namespace App\Providers;

use App\Http\Transformers\TransformerInterface;
use App\Services\SerializerService;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

/**
 * Provide 2 macro's to generate a response based on predefined transformers
 *
 * Class ApiResponseServicePRofiver
 * @package App\Providers
 */
class ApiResponseServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $serializer = $this->app->make('SerializerService');
        $headers = [
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'max-age=900',
        ];

        response()->macro(
            'item',
            function (
                TransformerInterface $transformer,
                $item,
                $status = 200,
                array $additionalHeaders = []
            ) use (
                $serializer,
                $headers
            ) {
                $request = app(Request::class);
                $serializer->setRequest($request);

                $headers = array_merge($headers,$additionalHeaders);

                return response(
                    $serializer->transformItem($transformer, $item),
                    $status,
                    $headers
                )->header('content-type', $serializer->getBestSupportedMimeType());
            }
        );

        response()->macro(
            'collection',
            function (
                TransformerInterface $transformer,
                $collection,
                $status = 200,
                array $additionalHeaders = []
            ) use (
                $serializer,
                $headers
            ) {
                $request = app(Request::class);
                $serializer->setRequest($request);

                $headers = array_merge($headers,$additionalHeaders);

                return response(
                    $serializer->transformCollection($transformer, $collection),
                    $status,
                    $headers
                )->header('content-type', $serializer->getBestSupportedMimeType());
            }
        );
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('SerializerService', function () {
            return \App\Services\SerializerService::getInstance();
        });
    }
}
