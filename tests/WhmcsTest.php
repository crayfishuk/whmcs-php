<?php

use PHPUnit\Framework\TestCase;
use Gufy\WhmcsPhp\Config;
use Gufy\WhmcsPhp\Whmcs;
use Gufy\WhmcsPhp\WhmcsResponse;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;

class WhmcsTest extends TestCase
{
    public $whmcs;

    public $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = new Config([
            'baseUrl' => 'http://localhost/includes/api.php',
            'username' => 'gufron',
            'password' => 'jago4n123',
        ]);
        $this->whmcs = new Whmcs($this->config);
        $content = array(
            json_encode(array('result' => 'success', 'clients' => array('client' => array()))),
            json_encode(array('result' => 'error', 'message' => 'Invalid IP : 127.0.0.1')),
            json_encode(array('result' => 'error', 'message' => 'commant not found')),
            json_encode(array('result' => 'success', 'clients' => array('client' => array()))),
            json_encode(array('result' => 'success', 'clients' => array('client' => array()))),
            json_encode(array('result' => 'success', 'clients' => array('client' => array())))
        );
        $mock = new MockHandler([
            new Response(200, ["Content-Type" => "text/json"], $content[0]),
            new Response(403, ["Content-Type" => "text/json"], $content[1]),
            new Response(403, ["Content-Type" => "text/json"], $content[2]),
            new Response(200, ["Content-Type" => "text/json"], $content[3]),
            new Response(200, ["Content-Type" => "text/json"], $content[4]),
            new Response(200, ["Content-Type" => "text/json"], $content[4]),
            new Response(200, ["Content-Type" => "text/json"], $content[4]),
        ]);
        $handler = HandlerStack::create($mock);
        $this->whmcs->client([
            'handler' => $handler,
        ]);
    }

    public function testCallApi()
    {
        $response = $this->whmcs->getclients();
        // print_r($response);
        $this->assertEquals(true, $response->isSuccess());
        $this->assertArrayHasKey('clients', $response);
        $this->assertEquals([], $response['clients']['client']);
    }

    public function testCallback()
    {
        $this->expectException("\Gufy\WhmcsPhp\Exceptions\ResponseException");
        $this->config->setBaseUrl('http://undefined/hello');
        // $this->config->setBaseUrl('http://undefined/hello');
        $response = $this->whmcs->getclients();
        // print_r($response);
        // print_r($response);
    }

    public function testClientException()
    {
        $this->expectException("\Gufy\WhmcsPhp\Exceptions\ResponseException");
        $whmcs = $this->whmcs;
        // $this->config->setBaseUrl('http://undefined/hello');
        $response = $whmcs->getnothing();
        // print_r($response);
    }

    public function testRespondedData()
    {
        $this->expectException("\Gufy\WhmcsPhp\Exceptions\ReadOnlyException");
        $whmcs = $this->whmcs;
        $response = $whmcs->getclients();
        $response['clients'] = 'helloworld';
    }

    public function testRespondedData2()
    {
        $this->expectException("\Gufy\WhmcsPhp\Exceptions\ReadOnlyException");
        $whmcs = $this->whmcs;
        $response = $whmcs->getclients();
        $this->assertTrue(isset($response['clients']));
        unset($response['clients']);
    }

    public function testUseApiKeys()
    {
        $this->config = new Config([
            'baseUrl' => 'http://localhost/includes/api.php',
            'username' => 'gufron',
            'password' => 'jago4n123',
            'authType' => 'keys'
        ]);
        $whmcs = new Whmcs($this->config);
        $response = $whmcs->getclients();
        $request = $whmcs->getRequest();
        $data = http_build_query([
            'action' => 'getclients',
            'username' => 'gufron',
            'accesskey' => 'jago4n123',
            'responsetype' => 'json'
        ]);
        $this->assertEquals('http://localhost/includes/api.php', $request->getUri());
        $this->assertEquals($data, $request->getBody()->getContents());
        // print_r($whmcs->getRequest());
        // $this->assertTrue(isset($response['clients']));
    }
}
