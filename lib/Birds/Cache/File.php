<?php
/**
 * Variable Caching and retieving
 *
 * This package implements a common interface for caching both in files or memory
 *
 * PHP version 5.3
 *
 * @category  Cache
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @version   SVN: $Id$
 * @link      http://tecnodz.com/
 */

/**
 * Variable Caching and retieving
 *
 * This package implements a common interface for caching both in files or memory
 *
 * @category  Cache
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @link      http://tecnodz.com/
 */
namespace Birds\Cache;
class File
{

    private static $_cacheDir=null;
    public static $serialize=true;

    public static function lastModified($key, $expires=0)
    {
        $lmod = @filemtime(self::filename($key));
        if ($lmod && (!$expires || $lmod > $expires)) {
            return $lmod;
        }
        return false;
    }

    public static function size($key, $expires=0)
    {
        return @filesize(self::filename($key));
    }

    public static function filename($key)
    {
        return self::cacheDir().'/'.$key.'.cache';
    }

    public static function cacheDir($s=null)
    {
        if (!is_null($s)) {
            self::$_cacheDir = $s;
        } else if (is_null(self::$_cacheDir)) {
            if(is_dir(BIRD_APP_ROOT.'/cache/Birds')) {
                self::$_cacheDir = BIRD_APP_ROOT.'/cache/Birds';
            } else {
                self::$_cacheDir = BIRD_VAR.'/cache';
            }
            $siteKey = \Birds\Cache::siteKey();
            if($siteKey) {
                self::$_cacheDir .= '/'.$siteKey;
            }
            unset($siteKey);
        }
        unset($s);
        return self::$_cacheDir;
    }

    /**
     * Gets currently stored key-pair value
     *
     * @param $key     mixed  key to be retrieved or array of keys to be tried (first available is returned)
     * @param $expires int    timestamp to be compared. If timestamp is newer than cached key, false is returned.
     * @param $method  mixed  Storage method to be used. Should be either a key or a value in self::$_methods
     */
    public static function get($key, $expires=0)
    {
        $cfile = self::filename($key);
        if (file_exists($cfile) && (!$expires || filemtime($cfile) > $expires)) {
            list($toexpire, $ret) = explode("\n", file_get_contents($cfile), 2);
            if($toexpire && $toexpire<BIRD_TIME) {
                @unlink($cfile);
                $ret = false;
            } else if (self::$serialize) {
                $ret = unserialize($ret);
            } else $ret=false;
        } else $ret=false;

        unset($cfile, $key, $expires);

        return $ret;
    }
    
    /**
     * Sets currently stored key-pair value
     *
     * @param $key     mixed  key(s) to be stored
     * @param $value   mixed  value to be stored
     * @param $expires int    timestamp to be set as expiration date.
     * @param $method  mixed  Storage method to be used. Should be either a key or a value in self::$_methods
     */
    public static function set($key, $value, $timeout=0)
    {
        if(self::$serialize) {
            $value = serialize($value);
        }
        if($timeout && $timeout<2592000) $timeout = BIRD_TIME+(float)$timeout;
        $ret = \bird::save(self::filename($key), ((float) $timeout)."\n".$value, true);
        unset($key,$value,$timeout);
        return $ret;
    }
    public static function delete($key)
    {
        $cfile = self::filename($key);
        @unlink($cfile);
        unset($cfile, $key);
        return true;
    }
}