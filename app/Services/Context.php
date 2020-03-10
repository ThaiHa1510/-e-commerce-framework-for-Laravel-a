<?php
namespace App\Services;
use \App\Contract\Context\Iface;
class Context implements Iface{
    private $config;
    public function __contruct( \Illuminate\Contracts\Config\Repository $config){
        $this->config=$config;
    }
}