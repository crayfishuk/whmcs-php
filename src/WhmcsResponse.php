<?php

namespace Gufy\WhmcsPhp;

use ArrayAccess;
use Gufy\WhmcsPhp\Exceptions\ResponseException;
use Gufy\WhmcsPhp\Exceptions\ReadOnlyException;

class WhmcsResponse implements ArrayAccess
{

    /**
     * Original array of response
     * @var array $response
     */
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
            throw new ResponseException($this->message);a
        }
    }

    /**
     * Check if the call was a success
     *
     * @return bool
     */
    public function isSuccess()
    {
        return $this->result == 'success';
    }

    /**
     *  Magic Getter
     * @param $var
     * @return mixed
     */
    public function __get($var)
    {
        return $this->response[ $var ];
    }

    public function offsetGet($var)
    {
        return $this->response[ $var ];
    }

    public function offsetSet($var, $value = '')
    {
        throw new ReadOnlyException($var);
    }

    public function offsetExists($var)
    {
        return isset($this->response[ $var ]);
    }

    public function offsetUnset($var)
    {
        throw new ReadOnlyException($var);
    }
}
