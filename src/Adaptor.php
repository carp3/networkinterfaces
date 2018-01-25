<?php
/*
 * This file is part of NetworkInterfaces.
 *
 * (c) Pedram Azimaie <carp3co@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NetworkInterfaces;


use Exception;

class Adaptor
{
    public $name;
    public $family;
    public $method;
    public $auto = false;
    public $allows = [];
    public $Unknown = [];
    protected $data = [];

    public function __get($name)
    {
        if (array_key_exists($name, $this->data))
            return $this->data[$name];
        throw new Exception("$name is not defined");
    }

    public function __set($name, $value)
    {
        if (in_array($name, ['address', 'netmask', 'gateway', 'broadcast', 'network']))
            if (!filter_var($value, FILTER_VALIDATE_IP))
                throw new Exception("$value is a valid IP address");
        $this->data[$name] = $value;

    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __unset($name)
    {
        unset($this->data[$name]);
    }
}