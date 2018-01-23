<?php
/**
 * Created by PhpStorm.
 * User: Pedram2
 * Date: 1/23/2018
 * Time: 5:34 PM
 */

namespace carp3\NetworkInterfaces;


use Exception;

class NetworkInterfaces
{
        private $_interfaceFile = false;

        public function __construct(string $InterfacePath = '/etc/network/interfaces' )
        {
            if(!@file_exists($InterfacePath))
                throw new Exception("Interface file does not exist");
            if(!@is_readable($InterfacePath))
                throw new Exception("Interface file is not readable");
            $this->_interface = $InterfacePath;
        }

    public function get(){

    }

    public function getDetail(string $name){

    }

    public function add(){

    }

    public  function up($name){

    }

    public function down($name){

    }

    public function test(){

    }

    public function write(){

    }
}