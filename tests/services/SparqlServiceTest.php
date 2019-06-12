<?php

namespace Tests\Services;

use App\Repositories\LodServicesRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;

class SparqlServiceTest extends \BrowserKitTestCase
{
    use DatabaseTransactions;

    /**
     * @var \App\Services\SparqlService
     */
    private $sparqlService;

    /**
     * @return null
     */
    public function setUp(): void
    {
        parent::setUp();

        if (env('APP_SKIP_TRAVIS_TEST')) {
            return;
        }

        $this->sparqlService = app('SparqlService');
        $this->sparqlService->setClient();
    }

    /**
     * @test
     * @group validation
     */
    public function testBaseConnectionTestWorksWithoutExceptionThrown()
    {
        if (env('APP_SKIP_TRAVIS_TEST')) {
            return;
        }
        app('SparqlService');
    }

    /**
     * @test
     * @group validation
     */
    public function testBaseConnectionGivesExceptionOnNotSparqlEndPoint()
    {
        if (env('APP_SKIP_TRAVIS_TEST')) {
            return;
        }
        $this->expectException(\Exception::class);
        $this->sparqlService->setClient('http://stad.gent');
    }

    /**
     * @test
     * @group validation
     */
    public function testBaseConnectionGivesExceptionOnNonexistingEndPoint()
    {
        if (env('APP_SKIP_TRAVIS_TEST')) {
            return;
        }
        $this->expectException(\GuzzleHttp\Exception\ConnectException::class);
        $this->sparqlService->setClient('http://thisIsNotAnEndpoint');
    }

    /**
     * @test
     * @group validation
     */
    public function testAuthenticationThrowsErrorWithWrongCredentials()
    {
        if (env('APP_SKIP_TRAVIS_TEST')) {
            return;
        }
        $this->expectException(\GuzzleHttp\Exception\ClientException::class);
        $this->sparqlService->setClient(
            env('SPARQL_WRITE_ENDPOINT'),
            'WrongUserName',
            'wrongPasw'
        );
    }

    /**
     * @test
     * @group functionality
     */
    public function testDeprecatedPerformSparqlQueryStillWorks()
    {
        if (env('APP_SKIP_TRAVIS_TEST')) {
            return;
        }
        $getServicesQuery = LodServicesRepository::getVestaServicesQuery(5);
        $services = $this->sparqlService->performSparqlQuery($getServicesQuery, 'GET');
        $this->assertTrue($this->sparqlService->getLastResponceCode() == 200);

        json_decode($services);
        $this->assertTrue(json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * @test
     * @group functionality
     */
    public function testRefactoredGet()
    {
        if (env('APP_SKIP_TRAVIS_TEST')) {
            return;
        }
        $getServicesQuery = LodServicesRepository::getRecreatexServicesQuery(5);
        $services = $this->sparqlService->get($getServicesQuery);
        $this->assertTrue($this->sparqlService->getLastResponceCode() == 200);

        json_decode($services);
        $this->assertTrue(json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * @test
     * @group scenario
     */
    public function testCRUDQueriesInFlow()
    {
        if (env('APP_SKIP_TRAVIS_TEST')) {
            return;
        }
        // see of no data to start with
        $this->checkReadResults();

        //CREATE
        $query = 'WITH <' . env('SPARQL_WRITE_GRAPH') . '> INSERT DATA { <http://example.org/> ' .
            '<http://example.org/foo> "a" }';
        $response = $this->sparqlService->post($query);
        // see if results are correct
        $data = json_decode($response, true);
        $resultString = Arr::get($data, 'results.bindings.0.callret-0.value');
        $succesString = 'Insert into <' . env('SPARQL_WRITE_GRAPH') . '>, 1 (or less) triples -- done';
        $this->assertEquals($succesString, $resultString);
        $this->assertEquals(200, $this->sparqlService->getLastResponceCode());

        //READ (already done once, but lets check if we have an "a" in there)
        $this->checkReadResults('a');

        //UPDATE (delete/insert)
        $query = 'WITH <' . env('SPARQL_WRITE_GRAPH') . '>
        DELETE { <http://example.org/> <http://example.org/foo> "a" }
        INSERT { <http://example.org/> <http://example.org/foo> "b" }
        WHERE {<http://example.org/> <http://example.org/foo> "a"} ';
        $response = $this->sparqlService->post($query);
        // see if results are correct
        $data = json_decode($response, true);
        $resultString = Arr::get($data, 'results.bindings.0.callret-0.value');
        $succesString = 'Modify <' . env('SPARQL_WRITE_GRAPH') . '>, delete 1 (or less) ' .
            'and insert 1 (or less) triples -- done';
        $this->assertEquals($succesString, $resultString);

        // check if "a" changed to "b"
        $this->checkReadResults('b');

        //DELETE
        $query = 'WITH <' . env('SPARQL_WRITE_GRAPH') . '> DELETE DATA { <http://example.org/> ' .
            '<http://example.org/foo> "b" }';
        $response = $this->sparqlService->get($query);
        // see if results are correct
        $data = json_decode($response, true);
        $resultString = Arr::get($data, 'results.bindings.0.callret-0.value');
        $succesString = 'Delete from <' . env('SPARQL_WRITE_GRAPH') . '>, 1 (or less) triples -- done';
        $this->assertEquals($succesString, $resultString);

        // see of no data to end with
        $this->checkReadResults();
    }

    /**
     * Perfoms a read query for testCRUDQueriesInFlow
     *
     * Is used for checking the situation in the database,
     * before/between/after the alternations of create update and delete
     *
     * When $endresult given: it exists existence in the data
     * When none given: checks or data is ectual empty
     *
     * @param $endValue
     */
    private function checkReadResults($endValue = false)
    {
        // check all data
        $query = 'WITH <' . env('SPARQL_WRITE_GRAPH') . '> SELECT ?value { <http://example.org/> ' .
            '<http://example.org/foo> ?value }';
        $response = $this->sparqlService->get($query);
        $this->assertEquals(200, $this->sparqlService->getLastResponceCode());
        // see if results are correct
        $data = json_decode($response, true);
        $subtest = Arr::get($data, 'results.bindings');
        $this->assertCount(($endValue ? 1 : 0), $subtest);
        // check end result
        if ($endValue) {
            $resultString = Arr::get($data, 'results.bindings.0.value.value');
            $this->assertEquals($endValue, $resultString);
        }
    }
}
