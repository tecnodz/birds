<?php
/**
 * Database abstraction
 *
 * PHP version 5.3
 *
 * @category  Data
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2014 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @version   SVN: $Id$
 * @link      http://tecnodz.com/
 */
namespace Birds;
class Data implements \ArrayAccess
{
    public function __construct($o=array())
    {
        foreach($o as $k=>&$v) {
            $this->$k = $v;
            unset($k, $v);
        }
    }

    public static function handler($s=null)
    {
        static $H = array();
        $s = ($s instanceof Schema)?($s):(Schema::load($s));
        $n = (isset($s->connection))?($s->connection):('');
        if(!isset($H[$n])) {
            $dbs = bird::app()->Data;
            $db = (!isset($dbs[$n]))?(array_shift($dbs)):($dbs[$n]);
            $cn = (isset($db['class']))?($db['class']):('Birds\\Data\\'.ucfirst(substr($db['dsn'], 0, strpos($db['dsn'], ':'))));
            $H[$n] = $cn;
            unset($dbs, $db);
        } else {
            $cn = $H[$n];
        }
        return new $cn($s);
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $i=0;
            while(isset($this->{$i})) {
                $i++;
            }
            $this->{$i} = $value;
        } else if($p=strpos($offset, '/')) {
            $b = substr($offset, 0, $p);
            $offset = substr($offset, $p+1);
            if(!isset($this->{$b})) {
                $this->{$b} = array();
            }
            $a = $this->{$b};
            if(strpos($offset, '/')) {
                @eval('$this->{$b}[\''.str_replace('/', '\'][\'', $offset).'\']=$value;');
            } else {
                $this->{$b}[$offset]=$value;
            }
        } else {
            $this->{$offset} = $value;
        }
    }
    public function offsetExists($offset) {
        if($p=strpos($offset, '/')) {
            $b = substr($offset, 0, $p);
            $offset = substr($offset, $p+1);
            if(isset($this->{$b}) && isset($this->{$b}[$offset])) {
                if(strpos($offset, '/')) {
                    $a = $this->{$b}[$offset];
                    while($p=strpos($offset, '/')) {
                        if(is_array($a) && isset($a[substr($offset, 0, $p)])) {
                            $a = $a[substr($offset, 0, $p)];
                        } else {
                            return false;
                        }
                    }
                    return true;
                }
                return true;
            }
            return false;
        }
        return isset($this->{$offset});
    }
    public function offsetUnset($offset) {
        if($p=strpos($offset, '/')) {
            $b = substr($offset, 0, $p);
            $offset = substr($offset, $p+1);
            if(isset($this->{$b})) {
                $a = $this->{$b};
                if(strpos($offset, '/')) {
                    @eval('unset($this->{$b}[\''.str_replace('/', '\'][\'', $offset).'\']);');
                } else {
                    unset($this->{$b}[$offset]);
                }
                return $a;
            }
            return false;
        }
        unset($this->{$offset});
    }

    public function offsetGet($offset) {
        if($p=strpos($offset, '/')) {
            $b = substr($offset, 0, $p);
            $offset = substr($offset, $p+1);
            if(isset($this->{$b}) && isset($this->{$b}[$offset])) {
                if(strpos($offset, '/')) {
                    $a = $this->{$b}[$offset];
                    while($p=strpos($offset, '/')) {
                        if(is_array($a) && isset($a[substr($offset, 0, $p)])) {
                            $a = $a[substr($offset, 0, $p)];
                        } else {
                            return false;
                        }
                    }
                    return $a;
                }
                return $this->{$b}[$offset];
            }
            return false;
        }
        return isset($this->{$offset}) ? $this->{$offset} : null;
    }
}