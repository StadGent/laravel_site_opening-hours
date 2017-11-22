<?php

namespace App\Providers;

use App\Http\Transformers\TransformerInterface;
use App\Services\SerializerService;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

/**
 * Provide 2 macro's to generate a response based on predefined transformers
 *
 * Class SerializerServiceProvider
 * @package App\Providers
 */
class SerializerServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $serializer = $this->app->make('SerializerService');

        response()->macro('item',
            function (
                TransformerInterface $transformer,
                $item,
                $status = 200,
                array $headers = []
            ) use (
                $serializer
            ) {
                $request = app(Request::class);
                $serializer->setRequest($request);

                return response(
                    $serializer->transformItem($transformer, $item),
                    $status,
                    $headers
                )->header('Access-Control-Allow-Origin', '*')
                    ->header('content-type', $serializer->getBestSupportedMimeType());
            }
        );

        response()->macro('collection',
            function (
                TransformerInterface $transformer,
                $collection,
                $status = 200,
                array $headers = []
            ) use (
                $serializer
            ) {
                $request = app(Request::class);
                $serializer->setRequest($request);

                return response(
                    $serializer->transformCollection($transformer, $collection),
                    $status,
                    $headers
                )->header('Access-Control-Allow-Origin', '*')
                    ->header('content-type', $serializer->getBestSupportedMimeType());

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
