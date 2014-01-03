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
class Memcached
{

    public static $memcachedServers=array('localhost:11211');
    private static $_memcached;

    public static function memcached()
    {
        if(is_null(self::$_memcached) && class_exists('Memcached')) {
            self::$_memcached=new \Memcached(self::siteKey());
            $conn=false;
            foreach(self::$memcachedServers as $s) {
                if(preg_match('/^(.*)\:([0-9]+)$/', $s, $m)) {
                    if(self::$_memcached->addServer($m[1], (int)$m[2])) $conn=true;
                } else if(self::$_memcached->addServer($s, 11211)) $conn=true;
                unset($s, $m);
            }
            if(!$conn) self::$_memcached=false;
            else {
                if($key=self::siteKey()) {
                    self::$_memcached->setOption(\Memcached::OPT_PREFIX_KEY, $key.'/');
                }
                unset($key);
            }
            unset($conn);

        }
        return self::$_memcached;
    } 

    public static function lastModified($key, $expires=0)
    {
        if(!self::memcached()) return File::lastModified($key, $expires);
        $lmod = self::$_memcached->get($key.'.expires');
        if ($expires) {
            if(!$lmod || $lmod < $expires) {
                return false;
            }
        }
        return $lmod;
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
        if(!self::memcached()) return File::get($key, $expires);
        if ($expires) {
            $kexpires = self::$_memcached->get($key.'.expires');
            if(!$kexpires || $kexpires < $expires) {
                return false;
            }
        }
        return self::$_memcached->get($key);
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
        if(!self::memcached()) return File::set($key, $value, $expires);
        if(!is_array($key)) {
            $key = array($key);
        }
        $keys = $key;
        $ttl = ($timeout)?($timeout - time()):($timeout);
        if($ttl<0) {// a timestamp should be supplied, not the seconds to expire?
            $ttl = $timeout;
        }
        foreach($keys as $key) {
            if(!self::$_memcached->set($key.'.expires', time(), $timeout) || !self::$_memcached->set($key, $value, $timeout)) {
                return false;
            }
        }
        return true;
    }

    public static function deleteMemcached($key)
    {
        if(!self::memcached()) return File::delete($key, $expires);
        if(self::$_memcached->deleteMulti($key.'.expires', $key)) {
            return true;
        } else {
            return false;
        }
    }
}