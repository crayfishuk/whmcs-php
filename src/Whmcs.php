<?php

namespace Gufy\WhmcsPhp;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Gufy\WhmcsPhp\Exceptions\ResponseException;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;

/**
 * Class Whmcs
 *
 * @package Gufy\WhmcsPhp
 * @method WhmcsResponse getclients()
 */
class Whmcs
{

    private $callbacks = [];

    /** @var Client */
    static  $CLIENT;

    /** @var RequestInterface */
    private $request;

    /**
     * Whmcs constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config =& $config;
    }

    /**
     * Static storage of the connection
     *
     * @param array $config
     * @return Client
     */
    public function client($config = [])
    {
        if (self::$CLIENT == null) {
            $config       = array_merge($config, []);
            self::$CLIENT = new Client($config);
        }
        return self::$CLIENT;
    }

    public function execute($action, $parameters = [])
    {
        $class      = $this;
        $tapHandler = Middleware::tap(function (RequestInterface $request) use ($class) {
            $class->setRequest($request);
        });

        $client        = $this->client();
        $clientHandler = $client->getConfig("handler");

        $parameters['action'] = $action;

        if ($this->config->getAuthType() == 'password') {
            $parameters['username'] = $this->config->getUsername();
            $parameters['password'] = $this->config->getPassword();
        } elseif ($this->config->getAuthType() == 'keys') {
            $parameters['identifier'] = $this->config->getUsername();
            $parameters['secret']     = $this->config->getPassword();
        }
        $parameters['responsetype'] = 'json';
        try {
            $response = $client->post($this->config->getBaseUrl(), ['form_params' => $parameters, 'timeout' => 1200, 'connect_timeout' => 10, 'handler' => $tapHandler($clientHandler)]);
            return $this->processResponse(json_decode($response->getBody()->getContents(), true));
        } catch (ClientException $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents(), true);
            throw new ResponseException($response['message']);
        }
    }

    /**
     * Setter for request
     *
     * @param RequestInterface $request
     */
    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Getter for request
     *
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Convert reponse to WhmcsResponse type
     *
     * @param $response
     * @return WhmcsResponse
     * @throws ResponseException
     */
    public function processResponse($response)
    {
        return new WhmcsResponse($response);
    }

    /**
     * Magic function to call remote API functions
     *
     * @param       $function
     * @param array $arguments
     * @return mixed
     */
    public function __call($function, array $arguments = [])
    {
        return call_user_func_array([$this, 'execute'], [$function, $arguments]);
    }
}
