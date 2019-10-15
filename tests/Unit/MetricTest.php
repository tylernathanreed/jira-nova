<?php

namespace Tests\Unit;

use Exception;
use Tests\TestCase;
use Laravel\Nova\Nova;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Metric;
use Laravel\Nova\Http\Requests\CardRequest;
use Laravel\Nova\Http\Requests\NovaRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MetricTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_index_metric_responses_for_resources_dont_explode()
    {
        // Determine the resources
        $resources = Nova::$resources;

        // Convert the resource class names into resource objects
        $resources = array_map(function($resource) {
            return new $resource($resource::newModel());
        }, $resources);

        // Create a new request instance
        $request = $this->app->make(NovaRequest::class);

        // Extract the available cards from each resource
        $cards = array_reduce($resources, function($cards, $resource) use ($request) {
            $cards[get_class($resource)] = $resource->availableCards($request);
            return $cards;
        }, []);

        // Flatten the list of cards
        $cards = Arr::collapse($cards);

        // Extract the metrics from the list of cards
        $metrics = array_filter($cards, function($card) {
            return $card instanceof Metric;
        });

        // Filter down to the metrics that appear on the index screen
        $indexMetrics = array_filter($metrics, function($metric) {
            return !$metric->onlyOnDetail;
        });

        // Resolve each metric
        $results = array_map(function($metric) use ($request) {

            try {
                $metric->resolve($request);
            }

            catch(Exception $ex) {
                throw new Exception(sprintf('Metric [%s] failed to resolve: %s', get_class($metric) . ':' . $metric->name, $ex->getMessage()), 0, $ex);
            }

            $this->addToAssertionCount(1);

        }, $indexMetrics);
    }
}
