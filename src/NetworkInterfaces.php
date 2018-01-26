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
 * Class NetworkInterfaces
 * @package NetworkInterfaces
 */
class NetworkInterfaces
{
    /**
     * @var array
     */
    public $Adaptors = [];
    /**
     * @var bool|string
     */
    private $_interfaceFile = false;
    /**
     * @var bool|string
     */
    private $_interfaceContent = '';
    /**
     * @var bool
     */
    private $_interfaceLoaded = false;
    /**
     * @var bool
     */
    private $_interfaceParsed = false;

    /**
     * NetworkInterfaces constructor.
     * @param string $InterfacePath
     * @param bool $new
     * @throws Exception
     */
    public function __construct(string $InterfacePath = '/etc/network/interfaces', $new = False)
    {
        $this->_interfaceFile = $InterfacePath;
        if ($new) {
            $this->_interfaceParsed = true;
            return;
        }
        if (!@file_exists($this->_interfaceFile))
            throw new Exception("Interface file does not exist");
        if (!@is_readable($this->_interfaceFile))
            throw new Exception("Interface file is not readable");
        $this->_interfaceContent = file_get_contents($this->_interfaceFile);
        $this->_interfaceLoaded = true;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function list()
    {
        if (!$this->_interfaceLoaded)
            throw new Exception("Interface file is not loaded");
        $interfaceContent = explode("\n", $this->_interfaceContent);
        $lastAdaptor = '';
        foreach ($interfaceContent as $item) {
            if (strpos(ltrim($item), '#') === 0) continue;
            if (trim($item) == '') continue;
            if (strpos($item, 'iface') === 0)
                $lastAdaptor = $this->_parseIface($item);
            elseif (strpos($item, 'auto') === 0)
                $this->_parseAuto($item);
            elseif (strpos($item, 'allow-') === 0)
                $this->_parseAllow($item);
            elseif (strpos($item, ' ') === 0)
                if ($lastAdaptor != '')
                    $this->_parseDetail($item, $lastAdaptor);
        }
        $this->_interfaceParsed = true;
        return $this->Adaptors;
    }

    /**
     * @param $item
     * @return mixed
     */
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
    private function _split($item, $adaptor = False, $returnAdaptor = false)
    {
        $chunks = preg_split('/\s+/', $item, -1, PREG_SPLIT_NO_EMPTY);
        if (!$adaptor) $this->_addAdaptor($chunks[1]);
        return $returnAdaptor ? $chunks[1] : $chunks;
    }


    /**
     * @param $adaptor
     */
    private function _addAdaptor($adaptor)
    {
        if (!array_key_exists($adaptor, $this->Adaptors)) $this->Adaptors[$adaptor] = new Adaptor();
    }

    /**
     * @param $item
     */
    private function _parseAuto($item)
    {
        $chunks = $this->_split($item);
        foreach (array_slice($chunks, 1) as $chunk) {
            $this->_addAdaptor($chunk);
            $this->Adaptors[$chunk]->auto = True;
        }

    }

    /**
     * @param $item
     */
    private function _parseAllow($item)
    {
        $chunks = $this->_split($item);
        $allow = str_replace('allow-', '', $chunks[0]);
        $allow = trim($allow);
        if (!in_array($allow, $this->Adaptors[$chunks[1]]->allows)) $this->Adaptors[$chunks[1]]->allows[] = $allow;
    }

    /**
     * @param $item
     * @param $lastAdaptor
     */
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

    /**
     * @param $name
     * @param bool $sudo
     * @throws Exception
     */
    public function up($name, $sudo = false)
    {
        if (!$this->_interfaceParsed)
            throw new Exception("Interface file is not parsed");
        if (!array_key_exists($name, $this->Adaptors))
            throw new Exception("$name does not exist is adaptor list");
        $cmd = ($sudo ? 'sudo ' : '') . "ifup $name";
        shell_exec($cmd);

    }

    /**
     * @param $name
     * @param bool $sudo
     * @throws Exception
     */
    public function down($name, $sudo = false)
    {
        if (!$this->_interfaceParsed)
            throw new Exception("Interface file is not parsed");
        if (!array_key_exists($name, $this->Adaptors))
            throw new Exception("$name does not exist is adaptor list");
        $cmd = ($sudo ? 'sudo ' : '') . "ifdown $name";
        shell_exec($cmd);
    }

    /**
     * @param $name
     * @param bool $sudo
     * @throws Exception
     */
    public function reset($name, $sudo = false)
    {
        if (!$this->_interfaceParsed)
            throw new Exception("Interface file is not parsed");
        if (!array_key_exists($name, $this->Adaptors))
            throw new Exception("$name does not exist is adaptor list");
        $cmd = ($sudo ? 'sudo ' : '') . "ifdown $name && " . ($sudo ? ' sudo ' : '') . "ifup $name";
        shell_exec($cmd);
    }

    /**
     * @param bool $return
     * @return bool|int|string
     * @throws Exception
     */
    public function write($return = False)
    {
        if (!$this->_interfaceParsed)
            throw new Exception("Interface file is not parsed");
        if (!@is_writable($this->_interfaceFile) && !$return)
            throw new Exception("Interface file is not writable");
        $knownAddresses = ['address', 'netmask', 'gateway', 'broadcast', 'network'];

        $buffer = [];
        $buffer[] = "#This file is generated by carp3/NetworkInterfaces library";
        $buffer[] = "#" . date('r');
        $buffer[] = '';
        foreach ($this->Adaptors as $adaptor => $detail) {
            if ($detail->auto) $buffer[] = "auto $adaptor";
            foreach ($detail->allows as $item)
                $buffer[] = "allow-$item $adaptor";
            $buffer[] = "iface $adaptor {$detail->family} {$detail->method}";
            foreach ($knownAddresses as $item)
                if (isset($detail->$item)) $buffer[] = " $item {$detail->$item}";
            foreach ($detail->Unknown as $item)
                $buffer[] = " $item";
            $buffer[] = '';
        }
        $imploded = implode("\n", $buffer);
        if ($return)
            return $imploded;
        return file_put_contents($this->_interfaceFile, $imploded);

    }


    /**
     * @param Adaptor $Adaptor
     * @throws Exception
     */
    public function add($Adaptor)
    {
        if (!$this->_interfaceParsed)
            throw new Exception("Interface file is not parsed");
        if (!array_key_exists($Adaptor->name, $this->Adaptors))
            throw new Exception("{$Adaptor->name} does not exist is adaptor list");
        $this->Adaptors[$Adaptor->name] = $Adaptor;
    }

}