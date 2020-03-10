<?php



namespace App\Facades;


/**
 * Returns the HTML clients
 *
 * @method static \Aimeos\Client\Html\Iface get()
 */
class Shop extends \Illuminate\Support\Facades\Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'shop';
    }
}
