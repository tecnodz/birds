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
namespace Birds;
class Cache
{
    public static $timeout = 0;
    private static $_storage=null;
    /**
     * Cache key used for storing this site information in memory, must be a 
     * unique string.
     * 
     * @var string
     */
    private static $_siteKey=null;

    public static function lastModified($key, $expires=0, $method=null)
    {
        $cn = '\\Birds\\Cache\\'.ucfirst(self::storage($method));
        if(is_array($key) && $key) {
            foreach($key as $ckey) {
                $ret2 = $cn::lastModified($ckey, $expires);
                if ($ret2>$ret) {
                    $ret = $ret2;
                }
                unset($ckey, $ret2);
            }
        } else {
            $ret = $cn::lastModified($key, $expires);
        }
        unset($fn, $key, $expires, $method);
        return $ret;
    }

    public static function storage($method=null)
    {
        if(!is_null($method)) {
            if(in_array($method, array('file', 'apc', 'memcache', 'memcached'))) return $method;
        }
        if(is_null(self::$_storage)) {
            if(ini_get('memcached.serializer')) self::$_storage='memcached';
            else if(function_exists('memcache_debug')) self::$_storage='memcache';
            else if(function_exists('apc_fetch')) self::$_storage='apc';
            else self::$_storage='file';
        }
        return self::$_storage;
    }

    /**
     * Gets currently stored key-pair value
     *
     * @param $key     mixed  key to be retrieved or array of keys to be tried (first available is returned)
     * @param $expires int    timestamp to be compared. If timestamp is newer than cached key, false is returned.
     * @param $method  mixed  Storage method to be used. Should be either a key or a value in self::$_methods
     */
    public static function get($key, $expires=0, $method=null, $fileFallback=false)
    {
        $cn = '\\Birds\\Cache\\'.ucfirst($method=self::storage($method));
        if(is_array($key)) {
            foreach($key as $ckey) {
                $ret = $cn::get($ckey, $expires);
                if ($ret) {
                    unset($ckey);
                    break;
                }
                unset($ckey,$ret);
            }
            if(!isset($ret)) $ret=false;
        } else {
            $ret = $cn::get($key, $expires);
        }
        if($fileFallback && $ret===false && $method!='file' && !$expires) {
            $ret = Cache\File::get($key);
            if($ret) {
                self::set($key, $ret);
            }
        }
        unset($cn, $key, $expires, $method);
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
    public static function set($key, $value, $timeout=0, $method=null, $fileFallback=false)
    {
        $cn = '\\Birds\\Cache\\'.ucfirst(self::storage($method));
        if(is_array($key)) {
            $ret = false;
            foreach($key as $ckey) {
                $ret = $cn::set($ckey, $value, $timeout);
                if (!$ret) {
                    break;
                }
                unset($ckey);
            }
        } else {
            $ret = $cn::set($key, $value, $timeout);
        }
        if($fileFallback && $ret===false && $method!='file' && !$expires) {
            $ret = Cache\File::set($key, $value);
        }
        unset($cn,$key,$value,$timeout,$method);
        return $ret;
    }

    public static function delete($key, $method=null)
    {
        $cn = '\\Birds\\Cache\\'.ucfirst(self::storage($method));
        if(is_array($key)) {
            $ret = false;
            foreach($key as $ckey) {
                $ret = $cn::delete($ckey);
                if (!$ret) {
                    break;
                }
                unset($ckey);
            }
            return $ret;
        }
        return $cn::delete($key);
    }

    /**
     * Defines a scope for this server cache space
     */
    public static function siteKey($s=null)
    {
        if (!is_null($s)) {
            self::$_siteKey = 'Birds/'.$s;
        } else if (is_null(self::$_siteKey)) {
            self::$_siteKey = false;
        }
        unset($s);
        return self::$_siteKey;
        
    }
    
}

stream_wrapper_register('cache', '\\Birds\\Cache\\Wrapper');
