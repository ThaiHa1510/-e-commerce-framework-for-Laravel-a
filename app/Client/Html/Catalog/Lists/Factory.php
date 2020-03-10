<?php
namespace App\Client\Html\Catalog\Lists;
use Exception;
class Factory extends \App\Client\Html\Common\Factory\Base implements \App\Client\Html\Common\Factory\Iface{

    private $context;
    public function create(App\Contract\Context\Iface $context, $path){
        if($path==null){
            $path=$context->getConfig()->get('client/html/catalog/lists/name', 'Standard');

        }
        if(ctype_alnum($path)){
            $classname = is_string( $name ) ? '\\Aimeos\\Client\\Html\\Catalog\\Lists\\' . $name : '<not a string>';
			throw new Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
        }
        $classname='\\App\\Client\\Html\\Calalog\\List'.$path;
        $iface='\\App\\Client\\Html\\Iface';
        $client = self::createClient( $context, $classname, $iface );
		$client = self::addClientDecorators( $context, $client, 'catalog/lists' );

		return $client->setObject( $client );

    }
}