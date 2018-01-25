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

class NetworkInterfaces
{
    public $Adaptors = [];
    private $_interfaceFile = false;
    private $_interfaceContent = '';
    private $_interfaceLoaded = false;

    public function __construct(string $InterfacePath = '/etc/network/interfaces', bool $lazyLoading = false)
    {
        $this->_interfaceFile = $InterfacePath;
        if (!@file_exists($this->_interfaceFile))
            throw new Exception("Interface file does not exist");
        if (!@is_readable($this->_interfaceFile))
            throw new Exception("Interface file is not readable");
        if (!$lazyLoading) {
            $this->_interfaceContent = file_get_contents($this->_interfaceFile);
            $this->_interfaceLoaded = true;
        }

    }

    public function list()
    {
        if (!$this->_interfaceLoaded)
            throw new Exception("Interface file is not loaded");
        $interfaceContent = explode("\n", $this->_interfaceContent);
        $lastAdaptor = '';
        foreach ($interfaceContent as $item) {
            if (strpos($item, '#') === 0) continue;
            if (trim($item) == '') continue;
            if (strpos($item, 'iface') === 0)
                $lastAdaptor = $this->_parseIface($item);
            if (strpos($item, 'auto') === 0)
                $this->_parseAuto($item);
            if (strpos($item, 'allow-') === 0)
                $this->_parseAllow($item);
            if (strpos($item, ' ') === 0) {
                if (strpos(ltrim($item), '#') === 0) continue;
                if ($lastAdaptor != '')
                    $this->_parseDetail($item, $lastAdaptor);
            }


        }
        return $this->Adaptors;
    }

    private function _parseIface($item)
    {
        $chunks = $this->_split($item);
        list($null, $this->Adaptors[$chunks[1]]->name, $this->Adaptors[$chunks[1]]->family, $this->Adaptors[$chunks[1]]->method) = $chunks;
        unset($null);
        return $chunks[1];
    }

    /**
     * @param $item
     * @return array
     */
    private function _split($item, $adaptor = False): array
    {
        $chunks = preg_split('/\s+/', $item, -1, PREG_SPLIT_NO_EMPTY);
        if (!$adaptor) if (!array_key_exists($chunks[1], $this->Adaptors)) $this->Adaptors[$chunks[1]] = new Adaptor();
        return $chunks;
    }

    private function _parseAuto($item)
    {
        $chunks = $this->_split($item);
        $this->Adaptors[$chunks[1]]->auto = True;
    }

    private function _parseAllow($item)
    {
        $chunks = $this->_split($item);
        $allow = str_replace('allow-', '', $chunks[0]);
        $allow = trim($allow);
        if (!in_array($allow, $this->Adaptors[$chunks[1]]->allows)) $this->Adaptors[$chunks[1]]->allows[] = $allow;
    }

    private function _parseDetail($item, $lastAdaptor)
    {
        $chunks = $this->_split($item, $lastAdaptor);
        $adaptor = &$this->Adaptors[$lastAdaptor];
        switch ($chunks[0]) {
            case 'address':
                $adaptor->address = $chunks[1];
                break;
            case 'netmask':
                $adaptor->netmask = $chunks[1];
                break;
            case 'gateway':
                $adaptor->gateway = $chunks[1];
                break;
            case 'broadcast':
                $adaptor->broadcast = $chunks[1];
                break;
            case 'network':
                $adaptor->network = $chunks[1];
                break;
            default:
                $adaptor->Unknown[] = trim($item);
                break;
        }
    }

    public function up($name)
    {

    }

    public function down($name)
    {

    }

    public function test()
    {

    }

    public function write()
    {
        if (!@is_writable($this->_interfaceFile))
            throw new Exception("Interface file is not writeable");
    }

}