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
class Apc
{

    public static function lastModified($key, $expires=0)
    {
        return self::get($key, $expires, 'modified');
    }

    public static function size($key, $expires=0)
    {
        return self::get($key, $expires, 'size');
    }

    /**
     * Gets currently stored key-pair value
     *
     * @param $key     mixed  key to be retrieved or array of keys to be tried (first available is returned)
     * @param $expires int    timestamp to be compared. If timestamp is newer than cached key, false is returned.
     * @param $method  mixed  Storage method to be used. Should be either a key or a value in self::$_methods
     */
    public static function get($key, $expires=0, $m=null)
    {
        if(!function_exists('apc_store')) return File::get($key, $expires, $m);
        $siteKey = \Birds\Cache::siteKey();
        if($siteKey) {
            $key = $siteKey.'/'.$key;
        }
        unset($siteKey);
        if ($expires || $m) {
            $meta = apc_fetch($key.'.meta');
            if($meta) list($lmod,$size)=explode(',',$meta);
            if($expires) {
                if(!$meta || !$lmod || $lmod < $expires) {
                    unset($meta, $lmod, $key, $expires, $size);
                    return false;
                }
            }
            if(!is_null($m)) {
                if($meta) {
                    unset($meta);
                    if($m=='size') return $size;
                    else if($m=='modified') return $lmod;
                }
                return false;
            }
            unset($meta);
        }
        unset($expires);
        return apc_fetch($key);
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
        if(!function_exists('apc_store')) return File::set($key, $value, $timeout);
        $ttl = ($timeout)?($timeout - time()):($timeout);
        if($ttl<0) {// a timestamp should be supplied, not the seconds to expire?
            $ttl = $timeout;
        }
        $siteKey = \Birds\Cache::siteKey();
        if(!is_array($key)) $key = array($key);
        foreach($key as $k) {
            if($siteKey) {
                $k = $siteKey.'/'.$k;
            }
            if(!apc_store($k.'.meta', time().','.(@strlen($value)), $timeout) || !apc_store($k, $value, $timeout)) {
                unset($ttl, $siteKey, $k);
                return false;
            }
            unset($k);
        }
        unset($ttl, $siteKey, $key);
        return true;
    }
    public static function delete($key)
    {
        if(!function_exists('apc_store')) return File::delete($key, $expires);

        $siteKey = \Birds\Cache::siteKey();
        if($siteKey) {
            $key = $siteKey.'/'.$key;
        }
        if(apc_delete($key.'.meta') && apc_delete($key)) {
            return true;
        } else {
            return false;
        }
    }
}