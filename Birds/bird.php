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
        $debugEnv=true,
        $lang='en',
        $lib=array(),
        $dateFormat='d/m/Y',
        $timeFormat='H:i',
        $decimals=2,
        $decimalSeparator=',',
        $thousandSeparator='.',
        $timeout,
        $vars=array();
    protected static $_name, $_env='prod', $_site, $_server, $app, $scriptName, $scriptRealName, $urlParam, $requestUri;

	/**
	 * Catches current bird application and route
	 */
	public static function app($name=null, $env=null, $timeout=null)
	{
        if(is_null($name) && !is_null(self::$app)) return self::$app;
        $name = self::name($name);
        if(is_null($timeout)) {
            if(is_null(self::$timeout)) {
                self::$timeout=0;
                $cfg=App::configFiles();
                foreach($cfg as $f) {
                    $t=filemtime($f);
                    if($t>self::$timeout) self::$timeout=$t;
                    unset($t,$f);
                }
                unset($cfg);
            }
            $timeout = self::$timeout;
        }
        self::$app = App::getInstance($name, self::env($env), $timeout);
        unset($name,$env,$timeout);
        return self::$app;
	}




    /**
     * Gets/sets current server name
     */
    public static function name($name=null)
    {
        if(!is_null($name)) {
            self::$_name=preg_replace('/[^a-z0-9\-\_]/i', '', $name);
            unset($name);
        } else if(is_null(self::$_name) && !(self::$_name = self::site())) {
            self::$_name = 'birds';
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
        unset($env);
        return self::$_env;
    }

    public static function site($site=null)
    {
        if(!is_null($site)) self::$_site=$site;
        else if(is_null(self::$_site)) {
            if(isset($_SERVER['HTTP_X_HOST'])) {
                $host=preg_replace('/[^a-z0-9\-\.]+/', '', strtolower($_SERVER['HTTP_X_HOST']));
            } else if(isset($_SERVER['HTTP_HOST'])) {
                $host=$_SERVER['HTTP_HOST'];
            } else {
                $host='localhost';
            }

            if(file_exists(BIRD_APP_ROOT.'/config/sites/'.$host.'.txt')
                || (($p=strpos($host, '.'))!==false && file_exists(BIRD_APP_ROOT.'/config/sites/'.($host=substr($host,$p+1)).'.txt'))
            ) {
                @list($site) = file(BIRD_APP_ROOT.'/config/sites/'.$host.'.txt');
            }
            if($site) {
                self::$_site = preg_replace('/[^a-z0-9\-\.]+/', '', strtolower($site));
            } else {
                self::$_site = '';
            }
            if (!defined('BIRD_SITE_ROOT')) {
                define('BIRD_SITE_ROOT', (is_dir(BIRD_APP_ROOT.'/sites/'.self::$_site))?(BIRD_APP_ROOT.'/sites/'.self::$_site):(BIRD_APP_ROOT));
            }
            unset($host, $site, $p);
        }
        return self::$_site;
    }

    /**
     * Gets current server's hostname
     */
    public static function serverName($server=null)
    {
        if(!is_null($server)) self::$_server=$server;
        if(is_null(self::$_server)) {
            if(!(self::$_server=Cache::getApc('hostname',0))){
                self::$_server=exec('hostname -f');
                if(!self::$_server) self::$_server = 'localhost.localdomain';
                Cache::setApc('hostname', self::$_server, 0);
            }
        }
        return self::$_server;
    }

    /**
     * Request method to get current script name. May act as a setter if a string is
     * passed. Also returns absolute script name (according to $_SERVER[REQUEST_URI])
     * if true is passed.
     *
     * @return string current script name
     */
    public static function scriptName($sn=null, $removeExtensions=false, $override=false)
    {
        if (!is_null($sn) || is_null(self::$scriptName)) {
            if($sn===false) {
                self::$scriptRealName=null;
            }  
            if(is_string($sn)) {
                self::$scriptName = $sn;
                if($override) self::$scriptRealName = $sn;
            } else {
                if (is_null(self::$scriptRealName)) {
                    if(isset($_SERVER['REDIRECT_STATUS']) && $_SERVER['REDIRECT_STATUS']=='200' && isset($_SERVER['REDIRECT_URL'])) {
                        self::$scriptRealName = $_SERVER['REDIRECT_URL'];
                    } else if (isset($_SERVER['REQUEST_URI'])) {
                        $qspos = strpos($_SERVER['REQUEST_URI'], '?');
                        if($qspos!==false) {
                            self::$scriptRealName = substr($_SERVER['REQUEST_URI'], 0, $qspos);
                        } else {
                            self::$scriptRealName = $_SERVER['REQUEST_URI'];
                        }
                        unset($qspos);
                    } else {
                        self::$scriptRealName = '';
                    }
                    // remove extensions
                    if($removeExtensions) {
                        self::$scriptRealName = preg_replace('#\.(php|html?)(/.*)?$#i', '$2', self::$scriptRealName);
                    }
                }
            }
            if(is_null(self::$scriptName)) self::$scriptName = self::$scriptRealName;
        }
        if($sn==true) {
            if(is_null(self::$scriptRealName)) self::$scriptRealName = self::$scriptName;
            return self::$scriptRealName;
        }
        return self::$scriptName;
    }

    public static function urlParam($url=null)
    {
        if(is_null($url)) {
            if(is_null(self::$scriptName)) return false;
            $url = self::$scriptName;
        }
        if(self::$scriptRealName!=$url && substr(self::$scriptRealName, 0, strlen($url))==$url) {
            return preg_split('#[/\\\\]+#', substr(self::$scriptRealName, strlen($url)), null, PREG_SPLIT_NO_EMPTY);
        } else {
            return false;
        }
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
        if (!headers_sent())
            @header("Content-Type: text/plain;charset=UTF-8");
        foreach (func_get_args() as $k=>$v) {
            if ($v === false)
                return false;
            print_r(self::string($v));
            echo "\n";
            unset($k, $v);
        }
        if(self::$debugEnv) echo '-- Time: '.self::number(microtime(true) - BIRD_TIME, 6).'s -- Mem: '.self::bytes(memory_get_usage())." -- \n";
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
        if((defined('BIRD_SITE_ROOT') && (is_writable($d=BIRD_SITE_ROOT.'/log/'.self::$_name.'.log') || is_writable(dirname($d)))) 
            || (is_writable($d=BIRD_APP_ROOT.'/log/'.self::$_name.'.log') || is_writable(dirname($d)))
        ) {
            $t = 3;
        } else  {
            $d = null;
            $t = 0;
        }
        foreach (func_get_args() as $k => $v) {
            error_log(self::string($v), $t, $d);
            unset($k,$v);
        }
        unset($d, $t);
    }

    /**
     * Stringfier for arrays and objects
     */
    public static function string($o, $i=0)
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
                    $s .= "\n" . self::string($v, $i);
                else
                    $s .= $v . "\n";
                unset($k, $v);
            }
            if (!$proc && is_object($o))
                $s .= $id . (string) $o;
            unset($proc);
        }
        else
            $s .= $id . $o;
        unset($id,$o);
        return $s . "\n";
    }

    /**
     * Text to Slug
     * 
     * @param string $str Text to convert to slug
     *
     * @return string slug
     */
    public static function slug($str, $accept='')
    {
        if($accept) $accept = preg_replace('/([^a-z0-9])/i', '\\\$1', $accept);
        else $accept = '_';
        $str = strtr(trim($str), array(
            'Š' => 'S', 'š' => 's', 'Đ' => 'Dj', 'đ' => 'dj', 'Ž' => 'Z',
            'ž' => 'z', 'Č' => 'C', 'č' => 'c', 'Ć' => 'C', 'ć' => 'c',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
            'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I',
            'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O',
            'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U',
            'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e',
            'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i',
            'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o',
            'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u',
            'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'ý' => 'y', 'þ' => 'b',
            'ÿ' => 'y', 'Ŕ' => 'R', 'ŕ' => 'r',
        ));
        $str = strtolower($str);
        $str = preg_replace('/[^a-z0-9-'.$accept.']+/', '-', $str);
        $str = preg_replace('/-+/', '-', $str);
        $str = preg_replace('/^-|-$/', '', $str);
        return $str;
    }

    /**
     * Configuration loader
     * 
     * loads cascading configuration files.
     * 
     * Syntax: tdz::config($env='prod', $section=null, $cfg1, $cfg2...)
     * 
     * @return array Configuration
     */
    public static function config()
    {
        $a = func_get_args();
        $env = 'prod';
        $envs = array('dev', 'prod', 'test', 'stage', 'maint');
        $section = false;
        foreach($a as $k=>$v) {
            if(is_object($v)) {
                $v = (array) $v;
            }
            if (is_array($v) || substr($v, -4)=='.yml') {
                unset($k, $v);
                continue;
            } else if (in_array($v, $envs)) {
                $env = $v;
            } else {
                $section = $v;
            }
            unset($a[$k], $k, $v);
        }
        $configs = array();
        foreach ($a as $s) {
            if (!is_array($s)) {
                $s = Yaml::load($s);

                if (!is_array($s)) {
                    continue;
                }
                if ($section) {
                    if(isset($s[$env][$section])) {
                        $configs[] = $s[$env][$section];
                    }
                    if(isset($s['all'][$section])) {
                        $configs[] = $s['all'][$section];
                    }
                } else {
                    if(isset($s[$env])) {
                        $configs[] = $s[$env];
                    }
                    if(isset($s['all'])) {
                        $configs[] = $s['all'];
                    }
                }
            } else {
                $configs[] = $s;
            }
            unset($s);
        }
        $res = call_user_func_array ('Birds\\bird::mergeRecursive', $configs);
        unset($configs, $a);
        return $res;
    }

    public static function mergeRecursive()
    {
        $a = func_get_args();
        $res = array_shift($a);
        foreach($a as $args) {
            foreach($args as $k=>$v) {
                if(!isset($res[$k])) {
                    $res[$k] = $v;
                } else if(is_array($res[$k]) && is_array($v)) {
                    $res[$k] = bird::mergeRecursive($v, $res[$k]);
                }
            }
        }
        return $res;
    }

    public static function recursiveReplace($s, $r, $a)
    {
        if(!is_array($a)) return str_replace($s, $r, $a);
        else
            foreach($a as $k=>$v)
                $a[$k] = self::recursiveReplace($s, $r, $v);
        return $a;
    }

    /**
     * Format bytes for humans
     *
     * @param float   $bytes     value to be formatted
     * @param integer $precision decimal units to use
     *
     * @return string formatted string
     */
    public static function bytes($bytes, $precision=null)
    {
        $u=array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, 8); // count($u) - 1 = 8
        $bytes /= pow(1024, $pow);

        $ret = self::number($bytes, $precision) . ' ' . $u[$pow];
        unset($bytes, $precision, $pow, $u);

        return $ret;
    }
    
    /**
     * Format numbers
     *
     * @param float   $bytes     value to be formatted
     * @param integer $precision decimal units to use
     *
     * @return string formatted string
     */
    public static function number($number, $decimals=null)
    {
        if(is_null($decimals)) $decimals=self::$decimals;
        return number_format($number, $decimals, self::$decimalSeparator, self::$thousandSeparator);
    }

    /**
     * Camelizes strings as class names
     * 
     * @param string $s
     * @return string Camelized Class name
     */
    public static function camelize($s)
    {
        return lcfirst(str_replace(' ', '', ucwords(preg_replace('/[^a-z0-9A-Z]+/', ' ', $s))));
    }

    /**
     * Uncamelizes strings as underscore_separated_names
     * 
     * @param string $s
     * @return string Uncamelized function/table name
     */
    public static function uncamelize($s)
    {
        return preg_replace('/[^a-z0-9A-Z]+([A-Za-z0-9])|([A-Z])/e', '"_".strtolower("$1$2")', $s);
    }

    /**
     * XML Escaping
     * 
     * Use this method to print content inside HTML/XML tags and attributes.
     * 
     * @param string $s text to be escaped
     * @param bool   $q escape quotes as well (defaults to true)
     * 
     * @return string escaped string
     */
    public static function xml($s, $q=true)
    {
        if (is_array($s)) {
            foreach ($s as $k => $v) {
                $s[$k] = tdz::xmlEscape($v);
                unset($k, $v);
            }
            return $s;
        }
        $qs = ($q) ? (ENT_QUOTES) : (ENT_NOQUOTES);
        return htmlspecialchars(html_entity_decode($s, $qs, 'UTF-8'), $qs, 'UTF-8', false);
    }

    /**
     * Compress Javascript & CSS
     */
    public static function minify($s, $root=false, $compress=true, $before=true, $raw=false)
    {
        return App\Assets::minify($s, $root, $compress, $before, $raw);
    }

    /**
     * File doenloader with support for HTTP 1.1
     */
    public static function download($file, $format=null, $fname=null, $speed=0, $attachment=false, $nocache=false, $exit=true)
    {
        return App\Assets::download($file, $format, $fname, $speed, $attachment, $nocache, $exit);
    }

    /**
     * Atomic file update
     *
     * Saves the $file with the $contents provided. If the file directory does not
     * exist, use $recursive=true to create it.
     *
     * @param string $file      the file to be saved
     * @param string $contents  the contents of the file to be saved
     * @param bool   $recursive whether the directory should be created if it doesn't
     *                          exist
     * @param binary $mask      octal mask to be applied to the file
     *
     * @return bool              true on success, false on error
     * @uses    xdb_pathtofile
     */
    public static function save($file, $contents, $recursive=false, $mask=0666) 
    {
        if ($file=='') {
            return false;
        }
        $dir = dirname($file);
        if (!is_dir($dir)) {
            if ($recursive) {
                $u=umask(0);
                mkdir($dir, $mask+0111, true);
                umask($u);
                unset($u);
            } else {
                return false;
            }
        }
        $tmpfile = tempnam($dir, '.' . basename($file));
        unset($dir);
        try {
            $fd = fopen($tmpfile, 'wb');            
            fwrite($fd, $contents);            
            fclose($fd);
            unset($fd);
            
            if (!chmod($tmpfile, $mask)) {
                throw new Exception("File \"{$file}\" could not be saved -- permission denied");
            } else if (!rename($tmpfile, $file)) {
                throw new Exception("File \"{$file}\" could not be saved -- permission denied");
            }
            unset($tmpfile, $file, $contents, $recursive, $mask);
            return true;
        } catch(Exception $e) {
            self::log('['.date('Y-m-d H:i:s').']'.' [error] ['.__METHOD__.': '.$e->getLine().'] '.$e->getMessage());
            unlink($tmpfile);
            unset($tmpfile, $file, $contents, $recursive, $mask);
            return false;
        }
    }
    /**
     * Class autoloader. Searches for classes under BIRD_ROOT./lib, BIRD_APP_ROOT./lib and self::$lib and 
     * 
     * @param string $c class name to be loaded
     * @param bool   $l if the class should be loaded
     *
     * @return mixed file location or false if not found.
     */
    public static function autoload($c, $l=true)
    {
        $c = str_replace(array('_', '\\'), '/', $c);
        if (!(file_exists($f=BIRD_ROOT."/lib/{$c}.php") || (strpos($c, '/')===false && file_exists($f=BIRD_ROOT."/lib/{$c}/{$c}.php")) || file_exists($f=BIRD_APP_ROOT."/lib/{$c}.php") || file_exists($f=BIRD_APP_ROOT."/lib/{$c}.class.php"))) {
            $f=false;
            foreach(self::$lib as $libi=>$dir) {
                if(substr($dir, -1)=='/') tdz::$lib[$libi]=substr($dir, 0, strlen($dir)-1);
                if(!(file_exists($f=$dir.'/'.$c.'.php') || file_exists($f=$dir.'/'.$c.'.class.php'))) $f=false;
                unset($libi,$dir);
            }
        }
        if($f) {
            if($l) @include_once $f;
            return $f;
        } else {
            return false;
        }
    }
}

define('BIRD_VERSION', 0.1);
if(!defined('BIRD_TIME')) {
	define('BIRD_TIME', (isset($_SERVER['REQUEST_TIME_FLOAT']))?($_SERVER['REQUEST_TIME_FLOAT']):(microtime(true)));
}
if (!defined('BIRD_ROOT')) {
    define('BIRD_ROOT', str_replace('\\', '/', dirname(__FILE__)));
}
if (!defined('BIRD_APP_ROOT')) {
    define('BIRD_APP_ROOT', (strrpos(BIRD_ROOT, '/lib/vendor/')!==false)?(substr(BIRD_ROOT, 0, strrpos(BIRD_ROOT, '/lib/vendor/'))):(BIRD_ROOT));
}
if (!defined('BIRD_CLI')) {
    define('BIRD_CLI', !isset($_SERVER['HTTP_HOST']));
}
if (!defined('BIRD_VAR')) {
    define('BIRD_VAR', BIRD_APP_ROOT.'/data');
}
spl_autoload_register('\Birds\bird::autoload');
if(BIRD_CLI && isset($_SERVER['argv'][0]) && substr(__FILE__, strlen($_SERVER['argv'][0])*-1)==$_SERVER['argv'][0]) {
    $app = bird::app();
    $app->fly();
    unset($app);
}
