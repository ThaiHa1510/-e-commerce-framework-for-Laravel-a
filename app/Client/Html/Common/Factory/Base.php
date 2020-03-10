<?php
namespace App\Client\Html\Common\Factory;

use Exception;

class Base
{
    private static $objects = [];

    public static function injectClient($classname, \App\Client\Html\Iface $client = null)
    {
        self::$objects[$classname] = $client;
    }

    /**
     * Adds the decorators to the client object.
     *
     * @param \Aimeos\MShop\Context\Item\Iface $context Context instance with necessary objects
     * @param \Aimeos\Client\Html\Iface $client Client object
     * @param array $decorators List of decorator name that should be wrapped around the client
     * @param string $classprefix Decorator class prefix, e.g. "\Aimeos\Client\Html\Catalog\Decorator\"
     * @return \Aimeos\Client\Html\Iface Client object
     */
    protected static function addDecorators(\App\Contract\Context\Item\Iface $context,
        \App\Client\Html\Iface $client, array $decorators, $classprefix) {
        foreach ($decorators as $name) {
            if (ctype_alnum($name) === false) {
                $classname = is_string($name) ? $classprefix . $name : '<not a string>';
                throw new \Aimeos\Client\Html\Exception(sprintf('Invalid class name "%1$s"', $classname));
            }

            $classname = $classprefix . $name;

            if (class_exists($classname) === false) {
                throw new \Aimeos\Client\Html\Exception(sprintf('Class "%1$s" not found', $classname));
            }

            $client = new $classname($client, $context);

            \Aimeos\MW\Common\Base::checkClass('\\Aimeos\\Client\\Html\\Common\\Decorator\\Iface', $client);
        }

        return $client;
    }

    protected static function addClientDecorators(\App\Contract\Context\Item\Iface $context,
        \Aimeos\Client\Html\Iface $client, $path) {
        if (!is_string($path) || $path === '') {
            throw new Exception(sprintf('Invalid domain "%1$s"', $path));
        }

        $localClass = str_replace(' ', '\\', ucwords(str_replace('/', ' ', $path)));
        $config = $context->getConfig();
        $decorators = $config->get('client/html/common/decorators/default', []);
        $excludes = $config->get('client/html/' . $path . '/decorators/excludes', []);

        foreach ($decorators as $key => $name) {
            if (in_array($name, $excludes)) {
                unset($decorators[$key]);
            }
        }

        $classprefix = '\\App\\Client\\Html\\Common\\Decorator\\';
        $client = self::addDecorators($context, $client, $decorators, $classprefix);

        $classprefix = '\\App\\Client\\Html\\Common\\Decorator\\';
        $decorators = $config->get('client/html/' . $path . '/decorators/global', []);
        $client = self::addDecorators($context, $client, $decorators, $classprefix);

        $classprefix = '\\App\\Client\\Html\\' . $localClass . '\\Decorator\\';
        $decorators = $config->get('client/html/' . $path . '/decorators/local', []);
        $client = self::addDecorators($context, $client, $decorators, $classprefix);

        return $client;
    }

    /**
     * Creates a client object.
     *
     * @param \Aimeos\MShop\Context\Item\Iface $context Context instance with necessary objects
     * @param string $classname Name of the client class
     * @param string $interface Name of the client interface
     * @return \Aimeos\Client\Html\\Iface Client object
     * @throws \Aimeos\Client\Html\Exception If client couldn't be found or doesn't implement the interface
     */
    protected static function createClient(\App\Contract\Context\Iface $context, $classname, $interface)
    {
        if (isset(self::$objects[$classname])) {
            return self::$objects[$classname];
        }

        if (class_exists($classname) === false) {
            throw new Exception(sprintf('Class "%1$s" not available', $classname));
        }

        $client = new $classname($context);

        //\App\MW\Common\Base::checkClass( $interface, $client );

        return $client;
    }
}
