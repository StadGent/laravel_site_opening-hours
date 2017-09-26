<?php

namespace Tests\Services;

use App\Repositories\LodServicesRepository;
use App\Services\SparqlService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SparqlServiceTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * @var mixed
     */
    private $sparqlService;

    public function setup()
    {
        parent::setUp();

        $this->sparqlService = app('SparqlService');
    }

    /**
     * @test
     */
    public function testDeprecatedGetServices()
    {
        $getServicesQuery = LodServicesRepository::getRecreatexServicesQuery();
        $services = $this->sparqlService->performSparqlQuery($getServicesQuery, 'GET', 'json');
        $this->assertTrue($this->sparqlService->getLastResponceCode() == 200);
        $this->assertTrue(!empty($services));
    }

    /**
     * @test
     */
    public function testGetServices()
    {
        $getServicesQuery = LodServicesRepository::getRecreatexServicesQuery();
        $services = $this->sparqlService->get($getServicesQuery, 'json');
        $this->assertTrue($this->sparqlService->getLastResponceCode() == 200);
        $this->assertTrue(!empty($services));
    }
}
