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

/**
 * Class Adaptor, represent an Adaptor
 * @package NetworkInterfaces
 */
class Adaptor
{
    /**
     * Interface name, usually ethXX for ethernet
     * @var string
     */
    public $name = "eth0";
    /**
     * Interface family, inet for ipv4 and inet6 for ipv6
     * @var string
     */
    public $family = "inet";
    /**
     * Interface method, dhcp , static or manual
     * @var string
     */
    public $method = "dhcp";
    /**
     * bring up interface automatically on startup
     * @var bool
     */
    public $auto = true;
    /**
     * allow option, usually hotplug
     * @var array
     */
    public $allows = [];
    /**
     * unrecognized options
     * @var array
     */
    public $Unknown = [];
    /**
     * @var array
     */
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