<?php
namespace App\Services;
class Shop{
    /**
     * @var App\Contract\Context\Iface;
     */
    private $context;
    /**
     * @var App\Contract\View\Iface;
     */
    private $view;
    /**
     * @var Object containt object saved
     */
    private $object=[];
    /**
     * @param $
     */
    public function __construct( \App\Contract\Context\Iface $context){
        $this->context=$context;
        //$this->view=$view->create();
    }
    /**
     * Function get views
     */
    public function get($name){
        if(!isset($object[$name])){
            /**
             * get views;
             */
            $client=\App\Client\Html::create($this->context,$name);
            $client->setView();
            $client->process();
            $object[$name]=$client;
            
        }
        return $object[$name];
    }

}