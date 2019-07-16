<?php 

namespace Gufy\WhmcsPhp;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Gufy\WhmcsPhp\Exceptions\ResponseException;
use Closure;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;

/**
 * Class Whmcs
 *
 * @package Gufy\WhmcsPhp
 *
 * The following methods are all available
 * @method WhmcsResponse GetPaymentMethods() =
 * (object)['totalresults' => 0, 'startnumer' => 0, 'numreturned' => 0, 'clients' => []]
 * @method WhmcsResponse GetProducts($params = ['pid' => 0, 'gid' => 0, 'module' => 'modulename'])
 * @method WhmcsResponse GetClients()
 * @method WhmcsResponse AddOrder($param = ['clientid'=>1,'pid'=> 2,'paymentmethod'=>'directdebit','billingcycle'  => 'monthly']) = (object)['orderid'=>0,'invoiceid'=>0]
 *
 *
 * // Partner Credits are 'one time' == 'monthly'
 * 'billingcycle'  => 'monthly',)
 */
class Whmcs
{

    private $callbacks = [];

    static $CLIENT;

    private $request;

    public function __construct(Config $config)
    {
        $this->config =& $config;
    }

    /**
     * Singleton class for the REST connection to the remote server (i.e. the REST client)
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

    /**
     * Call the WHMCS system with the action and parameters specified.
     *
     * Actually collects and construct the parameters for the API call - set $this->request and returns the response
     *
     * @param $action
     * @param $arguments
     * @return WhmcsResponse
     * @throws ResponseException
     */
    public function execute($action, $arguments)
    {
        // Create a handler for the response that sets $this->request
        $class      = $this;
        $tapHandler = Middleware::tap(function (RequestInterface $request) use ($class) {
            $class->setRequest($request);
        });

        $client        = $this->client();
        $clientHandler = $client->getConfig("handler");

        // First (and only) argument is the additional parameters for the API call
        if (isset($arguments[0])) {
            $parameters = $arguments[0];
        }

        // Get the action name from the magic function name and populate the other mandatory fields
        $parameters['action']   = $action;
        $parameters['username'] = $this->config->getUsername();

        if ($this->config->getAuthType() == 'password') {
            $parameters['username'] = $this->config->getPassword();
        } elseif ($this->config->getAuthType() == 'keys') {
            $parameters['password'] = $this->config->getPassword();
        }
        $parameters['responsetype'] = 'json';

        try {
            $response = $client->post($this->config->getBaseUrl(),
                                      [
                                          'form_params'     => $parameters,
                                          'timeout'         => 1200,
                                          'connect_timeout' => 10,
                                          'handler'         => $tapHandler($clientHandler),
                                      ]);
            return $this->processResponse(json_decode($response->getBody()->getContents(), true));

        } catch (ClientException $e) {

            $response = json_decode($e->getResponse()->getBody()->getContents(), true);
            throw new ResponseException($response['message']);

        }
    }

    /**
     * Save the request for access after the handler finishes
     *
     * @param RequestInterface $request
     */
    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Provide access to the request
     *
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Parse and process the response
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
     * Magic function that generates an API call for any action from the function name
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
