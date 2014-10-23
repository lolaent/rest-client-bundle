<?php
/**
 * Rest client tests
 *
 * @package Cti\RestClientBundle\Tests
 * @author  Georgiana Gligor <georgiana@cloudtroopers.com>
 */
namespace CTI\RestClientBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Cti\RestClientBundle\RestClient;

/**
 * Class RestClientTest
 */
class RestClientTest extends WebTestCase
{
    /** @var  RestClient */
    protected $restClient;

    /**
     * Set up function
     */
    public function setUp()
    {
        $client = static::createClient();
        $guzzleTimeout = $client->getContainer()->getParameter('guzzle_client_timeout');
        $this->restClient = new RestClient('http://httpbin.org/', $guzzleTimeout);
    }

    /**
     * Tests the behaviour with a successful response
     */
    public function testGetSuccessfully()
    {
        $response = $this->restClient->get('status/200')->send();

        $this->assertInstanceOf('\Guzzle\Http\Message\Response', $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Tests the behaviour with a not found page
     */
    public function testGetNotFound()
    {
        try {
            $response = $this->restClient->get('status/404')->send();
        } catch (\Exception $e) {
            $this->assertInstanceOf('\Guzzle\Http\Exception\ClientErrorResponseException', $e);
            $this->assertContains('[status code] 404', $e->getMessage());
        }
    }

    /**
     * Tests the behaviour with basic authentication
     */
    public function testGetWithBasicAuth()
    {
        $this->restClient->authenticate('user', 'passwd');
        $response = $this->restClient->get('basic-auth/user/passwd')->send();

        $this->assertInstanceOf('\Guzzle\Http\Message\Response', $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Tests the behaviour without basic authentication
     */
    public function testGetWithoutBasicAuth()
    {
        try {
            $response = $this->restClient->get('basic-auth/user/passwd')->send();
        } catch (\Exception $e) {
            $this->assertInstanceOf('\Guzzle\Http\Exception\ClientErrorResponseException', $e);
            $this->assertContains('[status code] 401', $e->getMessage());
        }
    }

    /**
     * Tests the behaviour with invalid basic authentication
     */
    public function testGetWithIncorrectBasicAuth()
    {
        try {
            $this->restClient->authenticate('incorrect-user', 'incorrect-passwd');
            $response = $this->restClient->get('basic-auth/user/passwd')->send();
        } catch (\Exception $e) {
            $this->assertInstanceOf('\Guzzle\Http\Exception\ClientErrorResponseException', $e);
            $this->assertContains('[status code] 401', $e->getMessage());
        }

        try {
            $this->restClient->authenticate('user', 'passwd');
            $response = $this->restClient->get('basic-auth/incorrect-user/incorrect-passwd')->send();
        } catch (\Exception $e) {
            $this->assertInstanceOf('\Guzzle\Http\Exception\ClientErrorResponseException', $e);
            $this->assertContains('[status code] 401', $e->getMessage());
        }
    }

    /** TODO add POST, PUT, DELETE protocol tests */

}
