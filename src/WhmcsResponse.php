<?php namespace Gufy\WhmcsPhp;

use ArrayAccess;
use Gufy\WhmcsPhp\Exceptions\ResponseException;
use Gufy\WhmcsPhp\Exceptions\ReadOnlyException;

/**
 * Class WhmcsResponse
 *
 * @package Gufy\WhmcsPhp
 * @property string $result
 *
 * Magic properties from WHMCS API return values - may or may not exist depending on API method used
 * @property int $totalresults
 *
 * // getProducts
 * @property array $products
 *
 * // getClients
 * @property array $clients
 *
 * // getPaymentMethodOptions & getPaymentMethods
 * @property array $paymentmethods
 *
 * // raiseNewCreditOrder
 * @property int $orderid
 * @property int $invoiceid
 */
class WhmcsResponse implements ArrayAccess
{

    /** @var WhmcsResponse */
    private $response;

    /**
     * WhmcsResponse constructor.
     *
     * @param $response
     * @throws ResponseException
     */
    public function __construct($response)
    {
        $this->response = $response;
        if (false === $this->isSuccess()) {
            throw new ResponseException($this->message);
        }
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->result == 'success';
    }

    /**
     * @param $var
     * @return mixed
     */
    public function __get($var)
    {
        return $this->response[ $var ];
    }

    /**
     * @param mixed $var
     * @return mixed
     */
    public function offsetGet($var)
    {
        return $this->response[ $var ];
    }

    /**
     * @param mixed  $var
     * @param string $value
     * @throws ReadOnlyException
     */
    public function offsetSet($var, $value = '')
    {
        throw new ReadOnlyException($var);
    }

    /**
     * @param mixed $var
     * @return bool
     */
    public function offsetExists($var)
    {
        return isset($this->response[ $var ]);
    }

    /**
     * @param mixed $var
     * @throws ReadOnlyException
     */
    public function offsetUnset($var)
    {
        throw new ReadOnlyException($var);
    }
}
