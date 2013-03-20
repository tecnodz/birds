<?php

/**
 * Birds framework
 *
 * PHP version 5.3
 *
 * @category  Core
 * @package   Birds
 * @author    Guilherme Capilé <capile@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @version   SVN: $Id$
 * @link      http://birds.tecnodz.com/
 */

/**
 * Birds framework
 *
 * @category  Core
 * @package   Birds
 * @author    Guilherme Capilé <capile@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @link      http://birds.tecnodz.com/
 */
namespace Birds;
class bird
{
	public static 
        $debugEnv=false,
        $lib=array(),
        $dateFormat='d/m/Y',
        $timeFormat='H:i',
        $decimals=2,
        $decimalSeparator=',',
        $thousandSeparator='.',
        $timeout=0;
        protected static $_name='birds', $_env='dev';

	/**
	 * Catches current bird application and route
	 */
	public static function app($name=null, $env=null, $timeout=null)
	{
        self::$debugEnv=true;
        if(is_null($timeout)) $timeout = self::$timeout;
        return App::getInstance(self::name($name), self::env($env), $timeout);
	}

    /**
     * Gets/sets current server name
     */
    public static function name($name=null)
    {
        if(!is_null($name)) self::$_name=preg_replace('/[^a-z0-9\-\_]/i', '', $name);
        if(file_exists($cfg=BIRD_APP_ROOT.'/config/'.self::$_name.'.yml')) {
            self::$timeout = filemtime($cfg);
        }
        Cache::siteKey(self::$_name);
        return self::$_name;
    }

    /**
     * Gets/sets current environment
     */
    public static function env($env=null)
    {
        if(!is_null($env) && preg_match('/^(dev|prod|test|stage)/', $env)) self::$_env=$env;
        return self::$_env;
    }


    /**
     * Debugging method
     *
     * Simple method to debug values - just outputs the value as text. The script
     * should end unless $end = FALSE is passed as param
     *
     * @param   mixed $var value to be displayed
     * @param   bool  $end should be FALSE to avoid the script termination
     *
     * @return  string text output of the $var definition
     */
    public static function debug()
    {
        $arg = func_get_args();
        if (!headers_sent())
            @header("Content-Type: text/plain;charset=UTF-8");
        foreach ($arg as $k => $v) {
            if ($v === false)
                return false;
            print_r(self::toString($v));
            echo "\n";
        }
        if(self::$debugEnv) echo '-- Time: '.self::formatNumber(microtime(true) - BIRD_TIME, 6).'s -- Mem: '.self::formatBytes(memory_get_usage()).' -- ';
        exit();
    }

    /**
     * Error messages logger
     *
     * Pretty print the objects to the PHP's error_log
     *
     * @param   mixed  $var  value to be displayed
     *
     * @return  void
     */
    public static function log()
    {
        $d = BIRD_VAR . '/log/birds.log';
        $t = 3;
        if(!file_exists($d) && !is_writable($d)) {
            $t = 0;
        }
        foreach (func_get_args() as $k => $v) {
            error_log(self::toString($v), $t, $d);
        }
    }

    /**
     * Stringfier for arrays and objects
     */
    public static function toString($o, $i=0)
    {
        $s = '';
        $id = str_repeat(" ", $i++);
        if (is_object($o)) {
            $s .= $id . get_class($o) . ":\n";
            $id = str_repeat(" ", $i++);
            if (method_exists($o, 'getData'))
                $o = $o->getData();
        }
        if (is_array($o) || is_object($o)) {
            $proc = false;
            foreach ($o as $k => $v) {
                $proc = true;
                $s .= $id . $k . ": ";
                if (is_array($v) || is_object($v))
                    $s .= "\n" . self::toString($v, $i);
                else
                    $s .= $v . "\n";
            }
            if (!$proc && is_object($o))
                $s .= $id . (string) $o;
        }
        else
            $s .= $id . $o;
        return $s . "\n";
    }


    /**
     * Format bytes for humans
     *
     * @param float   $bytes     value to be formatted
     * @param integer $precision decimal units to use
     *
     * @return string formatted string
     */
    public static function formatBytes($bytes, $precision=null)
    {
        $units = array('B', 'Kb', 'Mb', 'Gb', 'Tb');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return self::formatNumber($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Format numbers
     *
     * @param float   $bytes     value to be formatted
     * @param integer $precision decimal units to use
     *
     * @return string formatted string
     */
    public static function formatNumber($number, $decimals=null)
    {
        if(is_null($decimals)) $decimals=self::$decimals;
        return number_format($number, $decimals, self::$decimalSeparator, self::$thousandSeparator);
    }

    /**
     * Class autoloader. Searches for classes under BIRD_ROOT./lib, BIRD_APP_ROOT./lib and self::$lib and 
     * 
     * @param string $class class name to be loaded.
     *
     * @return mixed file location or false if not found.
     */
    public static function autoload($class, $load=true)
    {
        $c = str_replace(array('_', '\\'), '/', $class);
        if (!(file_exists($f=BIRD_ROOT."/lib/{$c}.php") || file_exists($f=BIRD_APP_ROOT."/lib/{$c}.php") || file_exists($f=BIRD_APP_ROOT."/lib/{$c}.class.php"))) {
            $f=false;
            foreach(self::$lib as $libi=>$dir) {
                if(substr($dir, -1)=='/') tdz::$lib[$libi]=substr($dir, 0, strlen($dir)-1);
                if(!(file_exists($f=$dir.'/'.$c.'.php') || file_exists($f=$dir.'/'.$c.'.class.php'))) $f=false;
            }
        }
        if($f) {
            if($load) @include_once $f;
            return $f;
        } else {
            return false;
        }
    }
}

define('BIRD_VERSION', 0.1);
$cfg = $_SERVER;
if(!defined('BIRD_TIME')) {
	$t = (isset($cfg['REQUEST_TIME_FLOAT']))?($cfg['REQUEST_TIME_FLOAT']):(microtime(true));
	define('BIRD_TIME', $t);
}
if (!defined('BIRD_ROOT')) {
    define('BIRD_ROOT', str_replace('\\', '/', dirname(__FILE__)));
}
if (!defined('BIRD_APP_ROOT')) {
    define('BIRD_APP_ROOT', (strrpos(BIRD_ROOT, '/lib/')!==false)?(substr(BIRD_ROOT, 0, strrpos(BIRD_ROOT, '/lib/'))):(BIRD_ROOT));
}
if (!defined('BIRD_CLI')) {
    define('BIRD_CLI', !isset($cfg['HTTP_HOST']));
}
if (!defined('BIRD_VAR')) {
    define('BIRD_VAR', BIRD_APP_ROOT.'/data/Birds');
}
spl_autoload_register('\Birds\bird::autoload');
if(BIRD_CLI && isset($cfg['argv'][0]) && substr(__FILE__, strlen($cfg['argv'][0])*-1)==$cfg['argv'][0]) {
    $app = bird::app();
    $app->fly();
}
