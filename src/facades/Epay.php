<?php namespace AngelBachev\Epay\Facades;
/**
 * Class Facade
 * @package AngelBachev\Epay\Facades
 * @see AngelBachev\Epay\Epay
 */
use Illuminate\Support\Facades\Facade;

class Epay extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'AngelBachev\Epay\Epay';
    }

}
