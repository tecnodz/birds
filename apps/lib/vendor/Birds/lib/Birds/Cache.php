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
    private static $_vars=array();
    public static $serialize=true;
    public static $memcachedServers=array('localhost:11211');
    private static $_cacheDir=null;
    private static $_storages=array('file', 'apc', 'memcache', 'memcached');
    private static $_storage=null;
    private static $_memcache, $_memcached;
    /**
     * Cache key used for storing this site information in memory, must be a 
     * unique string.
     * 
     * @var string
     */
    private static $_siteKey=null;

    public static function lastModified($key, $expires=0, $method=null)
    {
        bird::log(__METHOD__, func_get_args());
        $fn = 'lastModified'.ucfirst(self::storage($method));
        if(is_array($key)) {
            $ret = false;
            foreach($key as $ckey) {
                $ret2 = self::$fn($ckey, $expires);
                if ($ret2>$ret) {
                    $ret = $ret2;
                }
            }
            return $ret;
        }
        return self::$fn($ckey, $expires);
    }
    public static function getLastModified($key, $expires=0, $method=null){return self::lastModified($key, $expires, $method);}
    public static function lastModifiedMemcached($key, $expires=0)
    {
        if(!self::memcached()) return self::lastModifiedMemcache($key, $expires);
        $lmod = self::$_memcached->get($key.'.expires');
        if ($expires) {
            if(!$lmod || $lmod < $expires) {
                return false;
            }
        }
        return $lmod;
    }
    public static function lastModifiedMemcache($key, $expires=0)
    {
        if(!self::memcache()) return self::lastModifiedApc($key, $expires);
        $siteKey = self::siteKey();
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
    public static function lastModifiedApc($key, $expires=0)
    {
        $siteKey = self::siteKey();
        if($siteKey) {
            $key = $siteKey.'/'.$key;
        }
        $lmod = apc_fetch($key.'.expires');
        if ($expires) {
            if(!$lmod || $lmod < $expires) {
                return false;
            }
        }
        return $lmod;
    }
    public static function lastModifiedFile($key, $expires=0)
    {
        $cfile = self::filename($key);
        $lmod = @filemtime($cfile);
        if ($lmod && (!$expires || $lmod > $expires)) {
            return $lmod;
        }
        return false;
    }

    public static function filename($key)
    {
        bird::log(__METHOD__, func_get_args());
        return self::cacheDir().'/'.$key.'.cache';
    }


    public static function storage($method=null)
    {
        bird::log(__METHOD__, func_get_args());
        if(!is_null($method)) {
            if((is_numeric($method) || !is_string($method)) && isset(self::$_storages[$method])) return self::$_storages[$method];
            else if(in_array($method, self::$_storages)) return $method;
        }
        if(is_null(self::$_storage)) {
            return self::defaultStorage();
        }
        return self::$_storage;
    }

    public static function defaultStorage($method=null)
    {
        self::$_storage=false;
        if(!is_null($method) && self::$_storage=self::storage($method)) {
            return self::$_storage;
        }
        if(self::memcached()) self::$_storage='memcached';
        else if(self::memcache()) self::$_storage='memcache';
        else if(self::apc()) self::$_storage='apc';
        else self::$_storage='file';

        return self::$_storage;
    }


    public static function memcached()
    {
        if(is_null(self::$_memcached) && class_exists('Memcached')) {
            self::$_memcached=new \Memcached(self::siteKey());
            $conn=false;
            foreach(self::$memcachedServers as $s) {
                if(preg_match('/^(.*)\:([0-9]+)$/', $s, $m)) {
                    if(self::$_memcached->addServer($m[1], (int)$m[2])) $conn=true;
                } else if(self::$_memcached->addServer($s, 11211)) $conn=true;
            }
            if(!$conn) self::$_memcached=false;
            else {
                if($key=self::siteKey()) {
                    self::$_memcached->setOption(\Memcached::OPT_PREFIX_KEY, $key.'/');
                }
            }
        }
        return self::$_memcached;
    } 

    public static function memcache()
    {
        if(is_null(self::$_memcache) && class_exists('Memcache')) {
            self::$_memcache=new \Memcache();
            $conn=false;
            foreach(self::$memcachedServers as $s) {
                if(preg_match('/^(.*)\:([0-9]+)$/', $s, $m)) {
                    if(self::$_memcache->connect($m[1], (int)$m[2])) $conn=true;
                } else if(self::$_memcache->connect($s, 11211)) $conn=true;
            }
            if(!$conn) self::$_memcache=false;
        }
        return self::$_memcache;
    }

    public static function apc()
    {
        return function_exists('apc_store');
    } 

    
    /**
     * Gets currently stored key-pair value
     *
     * @param $key     mixed  key to be retrieved or array of keys to be tried (first available is returned)
     * @param $expires int    timestamp to be compared. If timestamp is newer than cached key, false is returned.
     * @param $method  mixed  Storage method to be used. Should be either a key or a value in self::$_methods
     */
    public static function get($key, $expires=0, $method=null)
    {
        bird::log(__METHOD__, func_get_args());
        $fn = 'get'.ucfirst(self::storage($method));
        if(is_array($key)) {
            $ret = false;
            foreach($key as $ckey) {
                $ret = self::$fn($ckey, $expires);
                if ($ret) {
                    break;
                }
            }
            return $ret;
        }
        return self::$fn($key, $expires);
    }
    public static function getMemcached($key, $expires=0)
    {
        bird::log(__METHOD__, func_get_args());
        if(!self::memcached()) return self::getMemcache($key, $expires);
        if ($expires) {
            $kexpires = self::$_memcached->get($key.'.expires');
            if(!$kexpires || $kexpires < $expires) {
                return false;
            }
        }
        return self::$_memcached->get($key);
    }
    public static function getMemcache($key, $expires=0)
    {
        bird::log(__METHOD__, func_get_args());
        if(!self::memcache()) return self::getApc($key, $expires);

        $siteKey = self::siteKey();
        if($siteKey) {
            $key = $siteKey.'/'.$key;
        }
        if ($expires) {
            $kexpires = self::$_memcache->get($key.'.expires');
            if(!$kexpires || $kexpires < $expires) {
                return false;
            }
        }
        return self::$_memcache->get($key);
    }
    public static function getApc($key, $expires=0)
    {
        bird::log(__METHOD__, func_get_args());
        if(!function_exists('apc_store')) return self::getFile($key, $expires);

        $siteKey = self::siteKey();
        if($siteKey) {
            $key = $siteKey.'/'.$key;
        }
        if ($expires) {
            $kexpires = apc_fetch($key.'.expires');
            if(!$kexpires || $kexpires < $expires) {
                return false;
            }
        }
        return apc_fetch($key);
    }
    public static function getFile($key, $expires=0)
    {
        bird::log(__METHOD__, func_get_args());
        $cfile = self::filename($key);
        if (file_exists($cfile) && (!$expires || filemtime($cfile) > $expires)) {
            list($toexpire, $cvar) = explode("\n", file_get_contents($cfile), 2);
            if($toexpire && $toexpire<BIRD_TIME) {
                @unlink($cfile);
                return false;
            }
            if (self::$serialize) {
                $cvar = unserialize($cvar);
            }
            return $cvar;
        }
        return false;
    }

    /**
     * Sets currently stored key-pair value
     *
     * @param $key     mixed  key(s) to be stored
     * @param $value   mixed  value to be stored
     * @param $expires int    timestamp to be set as expiration date.
     * @param $method  mixed  Storage method to be used. Should be either a key or a value in self::$_methods
     */
    public static function set($key, $value, $timeout=0, $method=null)
    {
        bird::log(__METHOD__, func_get_args());
        $fn = 'set'.ucfirst(self::storage($method));
        if(is_array($key)) {
            $ret = false;
            foreach($key as $ckey) {
                $ret = self::$fn($ckey, $value, $timeout);
                if (!$ret) {
                    break;
                }
            }
            return $ret;
        }
        return self::$fn($key, $value, $timeout);
    }

    public static function setMemcached($key, $value, $timeout=0)
    {
        bird::log(__METHOD__, func_get_args());
        if(!self::memcached()) return self::setMemcache($key, $expires);
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
    public static function setMemcache($key, $value, $timeout=0)
    {
        bird::log(__METHOD__, func_get_args());
        if(!self::memcache()) return self::setApc($key, $expires);

        $siteKey = self::siteKey();
        if(!is_array($key)) {
            $key = array($key);
        }
        if($siteKey) {
            foreach($key as $kk=>$kv) {
                $key[$kk] = $siteKey.'/'.$kv;
            }
        }
        $keys = $key;
        $ttl = ($timeout)?($timeout - time()):($timeout);
        if($ttl<0) {// a timestamp should be supplied, not the seconds to expire?
            $ttl = $timeout;
        }
        foreach($keys as $key) {
            if(!self::$_memcache->set($key.'.expires', time(), 0, $timeout) || !self::$_memcache->set($key, $value, 0, $timeout)) {
                return false;
            }
        }
        return true;
    }
    public static function setApc($key, $value, $timeout=0)
    {
        bird::log(__METHOD__, func_get_args());
        if(!function_exists('apc_store')) return self::setFile($key, $expires);

        $siteKey = self::siteKey();
        if(!is_array($key)) {
            $key = array($key);
        }
        if($siteKey) {
            foreach($key as $kk=>$kv) {
                $key[$kk] = $siteKey.'/'.$kv;
            }
        }
        $keys = $key;
        $ttl = ($timeout)?($timeout - time()):($timeout);
        if($ttl<0) {// a timestamp should be supplied, not the seconds to expire?
            $ttl = $timeout;
        }
        foreach($keys as $key) {
            if(!apc_store($key.'.expires', time(), $timeout) || !apc_store($key, $value, $timeout)) {
                return false;
            }
        }
        return true;
    }

    public function setFile($key, $value, $timeout=0)
    {
        bird::log(__METHOD__, func_get_args());
        $timeout = (int) $timeout;
        if(self::$serialize) {
            $value = serialize($value);
        }
        return birds::save(self::filename($key), $timeout."\n".$value, true);
    }

    public static function delete($key, $method=null)
    {
        bird::log(__METHOD__, func_get_args());
        $fn = 'delete'.ucfirst(self::storage($method));
        if(is_array($key)) {
            $ret = false;
            foreach($key as $ckey) {
                $ret = self::$fn($ckey);
                if (!$ret) {
                    break;
                }
            }
            return $ret;
        }
        return self::$fn($key);
    }
    public static function deleteMemcached($key)
    {
        bird::log(__METHOD__, func_get_args());
        if(!self::memcached()) return self::deleteMemcache($key, $expires);
        if(self::$_memcached->deleteMulti($key.'.expires', $key)) {
            return true;
        } else {
            return false;
        }
    }
    public static function deleteMemcache($key)
    {
        bird::log(__METHOD__, func_get_args());
        if(!self::memcache()) return self::deleteApc($key, $expires);

        $siteKey = self::siteKey();
        if($siteKey) {
            $key = $siteKey.'/'.$key;
        }
        if(self::$_memcache->delete($key.'.expires') && self::$_memcache->delete($key)) {
            return true;
        } else {
            return false;
        }
    }
    public static function deleteApc($key)
    {
        bird::log(__METHOD__, func_get_args());
        if(!function_exists('apc_store')) return self::deleteFile($key, $expires);

        $siteKey = self::siteKey();
        if($siteKey) {
            $key = $siteKey.'/'.$key;
        }
        if(apc_delete($key.'.expires') && apc_delete($key)) {
            return true;
        } else {
            return false;
        }
    }
    public static function deleteFile($key)
    {
        bird::log(__METHOD__, func_get_args());
        $cfile = self::filename($key);
        @unlink($cfile);
        return true;
    }

    /**
     * Defines a scope for this server cache space
     */
    public static function siteKey($s=null)
    {
        bird::log(__METHOD__, func_get_args());
        if (!is_null($s)) {
            self::$_siteKey = $s;
        } else if (is_null(self::$_siteKey)) {
            self::$_siteKey = false;
        }
        return self::$_siteKey;
        
    }
    
    public static function cacheDir($s=null)
    {
        bird::log(__METHOD__, func_get_args());
        if (!is_null($s)) {
            self::$_cacheDir = $s;
        } else if (is_null(self::$_cacheDir)) {
            self::$_cacheDir = BIRD_APP_ROOT.'/cache/Birds';
        }
        return self::$_cacheDir;
    }

}