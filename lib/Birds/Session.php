<?php
/**
 * Simple session persistence based on cookies
 *
 * PHP version 5.3
 *
 * @category  Session
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @version   SVN: $Id$
 * @link      http://tecnodz.com/
 */

/**
 * Simple session persistence based on cookies
 *
 * @category  Session
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @link      http://tecnodz.com/
 */
namespace Birds;
class Session extends Data {
    public static $name, $id, $expires=0;

    public function __toString()
    {
        bird::debug((array)$this);
    }

    public static function name()
    {
        if(is_null(self::$name)) {
            self::$name = bird::site();
            if(!self::$name) {
                self::$name = 'birdid';
            }
        }
        return self::$name;
    }

    public static function id()
    {
        if(is_null(self::$id)) {
            self::create();
        }
        return self::$id;
    }

    public static function restore($sid)
    {
        return Cache::get('session/'.$sid, Session::$expires);
    }

    public static function create($d=null)
    {
        if(is_null(bird::$session)) {
            foreach(bird::cookies($n=Session::name()) as $c) {// get cookie id from config
                if($u=Session::restore($c)) {
                    break;
                }
                unset($u, $c);
            }
            if(!isset($u)) {
                // if(!isset($c))  // reset cookie id
                $c = uniqid();
                bird::$session = new Session($d);
            } else {
                bird::$session = $u;
            }
            Session::$id = $c;
            setcookie($n, $c, (Session::$expires>2592000 || Session::$expires<=0)?(Session::$expires):(time()+Session::$expires), '/', null, false, true);
            unset($u, $c, $n);
        }
        return bird::$session;
    }

    public static function store()
    {
        Cache::set('session/'.Session::$id, bird::$session, Session::$expires);
    }

    public static function destroy()
    {
        if(is_null(self::$id)) {
            self::create();
        }
        Cache::delete('session/'.Session::$id);
        setcookie($n, '', -86400, '/', null, false, true);
    }

    /*
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
    */
}