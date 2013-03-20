<?php
/**
 * Birds Application Server
 *
 * This package enable Birds routes and controller
 *
 * PHP version 5.3
 *
 * @category  App
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @version   SVN: $Id$
 * @link      http://tecnodz.com/
 */

/**
 * Tecnodesign Application Server
 *
 * This package enable Tecnodesign application management.
 *
 * @category  App
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @link      http://tecnodz.com/
 */
namespace Birds;
class App
{

	public function __construct()
	{
		bird::debug(__METHOD__, false);
	}

	public function __wakeup()
	{
		bird::debug(__METHOD__, false);
	}

	public function fly()
	{
		bird::debug(__METHOD__, false);
	}

	public function __toString()
	{
		return 'Bird Application: '.bird::name().'/'.bird::env();
	}

	/**
	 * App recovery: tries to get existing app from variable or memory (cache)
	 */
	protected static $_instances;
    public static function getInstance($name=false, $env=false, $expires=0)
    {
        if (!$name) $name = birds::name();
        if (!$env)  $env  = birds::env();
        $instance="{$name}/{$env}";
        $ckey="app/{$env}";
        if(is_null(self::$_instances)) {
            self::$_instances = new \ArrayObject();
        }
        $app = false;
        if(isset(self::$_instances[$instance])) {
            $app = self::$_instances[$instance];
        } else if(!($app=Cache::get($ckey, $expires))) {
            $app = new App($name, $env);
            if($app) {
                Cache::set($ckey, $app, $expires);
                self::$_instances[$instance] = $app;
            }
        }
        return $app;
    }
}