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
class Session implements \ArrayAccess {
    public static $name, $id, $expires=3600;

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
            bird::session();
        }
        return self::$id;
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $i=0;
            while(isset($this->{$i})) {
                $i++;
            }
            $this->{$i} = $value;
        } else {
            $this->{$offset} = $value;
        }
    }
    public function offsetExists($offset) {
        return isset($this->{$offset});
    }
    public function offsetUnset($offset) {
        unset($this->{$offset});
    }
    public function offsetGet($offset) {
        return isset($this->{$offset}) ? $this->{$offset} : null;
    }

}