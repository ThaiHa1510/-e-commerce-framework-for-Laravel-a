<?php
namespace App\Services;

class View {
    /**
     * @var views object
     */
    private $view;
    /**
     * @var Context object
     */
    private $context;

    public function __contruct(){

    }
    public function create(){
        $view=$app['view'];
    }
}