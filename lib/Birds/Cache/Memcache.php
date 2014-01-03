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
class Memcache
{

    public static $memcachedServers=array('localhost:11211');
    private static $_memcache;

    public static function memcache()
    {
        if(is_null(self::$_memcache) && function_exists('memcache_debug')) {
            self::$_memcache=new \Memcache();
            $conn=false;
            foreach(self::$memcachedServers as $s) {
                if(preg_match('/^(.*)\:([0-9]+)$/', $s, $m)) {
                    if(self::$_memcache->connect($m[1], (int)$m[2])) $conn=true;
                } else if(self::$_memcache->connect($s, 11211)) $conn=true;
                unset($s, $m);
            }
            if(!$conn) self::$_memcache=false;
            unset($conn);
        }
        return self::$_memcache;
    }

    public static function lastModified($key, $expires=0)
    {
        if(!self::memcache()) return File::lastModified($key, $expires);
        $siteKey = \Birds\Cache::siteKey();
        if($siteKey) {
            $key = $siteKey.'/'.$key;
        }
        $lmod = self::$_memcache->get($key.'.expires');
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
        if(!self::memcache()) return File::get($key, $expires);

        $siteKey = self::siteKey();
        if($siteKey) {
            $key = $siteKey.'/'.$key;
        }
        unset($siteKey);
        if ($expires) {
            $kexpires = self::$_memcache->get($key.'.expires');
            if(!$kexpires || $kexpires < $expires) {
                unset($kexpires);
                return false;
            }
            unset($kexpires);
        }
        return self::$_memcache->get($key);
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
        if(!self::memcache()) return File::set($key, $value, $expires);

        $siteKey = \Birds\Cache::siteKey();
        if(!is_array($key)) {
            $keys = array($key);
        } else $keys=$key;
        if($siteKey) {
            foreach($keys as $kk=>$kv) {
                $keys[$kk] = $siteKey.'/'.$kv;
                unset($kk,$kv);
            }
        }
        unset($siteKey);
        //$ttl = ($timeout)?($timeout - time()):($timeout);
        //if($ttl<0) {// a timestamp should be supplied, not the seconds to expire?
        //    $ttl = $timeout;
        //}
        $ret = true;
        foreach($keys as $key) {
            if(!self::$_memcache->set($key.'.expires', time(), 0, $timeout) || !self::$_memcache->set($key, $value, 0, $timeout)) {
                $ret = false;
                break;
            }
            unset($key);
        }

        unset($keys,$key,$value,$timeout);
        return $ret;
    }
    public static function delete($key)
    {
        if(!self::memcache()) return File::delete($key, $expires);

        $siteKey = \Birds\Cache::siteKey();
        if($siteKey) {
            $key = $siteKey.'/'.$key;
        }
        if(self::$_memcache->delete($key.'.expires') && self::$_memcache->delete($key)) {
            return true;
        } else {
            return false;
        }
    }}