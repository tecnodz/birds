<?php
/**
 * Schema
 *
 * PHP version 5.3
 *
 * @category  Schema
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2014 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @version   SVN: $Id$
 * @link      http://tecnodz.com/
 */

/**
 * Model
 *
 * @category  Schema
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2014 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @link      http://tecnodz.com/
 */
namespace Birds;
class Schema extends Data
{

    public static $defaultItemType='Dataset', $cms, $schemas=array();
    protected static $instances=array();
    public $instance, $class, $table, $columns, $relations, $scope;

    public function __toString()
    {
        bird::debug((array)$this);
    }

    public function getScope($n='')
    {
        if($n && isset($this->scope) && isset($this->scope[$n])) {
            return $this->scope[$n];
        } else if(isset($this->columns)) {
            return array_keys($this->columns);
        } else {
            return array();
        }
    }

    public static function create($cn, $id=null)
    {
        bird::log(__METHOD__, func_get_args());
    }

    public static function attributes($cn, $id=null)
    {
        if(is_null(self::$cms)) {
            self::$cms = \Birds\bird::app()->Birds['cms'];
        }
        if(!self::$cms) return array();
        $p = array('itemscope'=>true);
        if(property_exists($cn, 'itemtype')) {
            $p['itemtype']=$cn::$itemtype;
            $s = (property_exists($cn, 'schemaid'))?($cn::$schemaid):(bird::encrypt($cn, 'uuid'));
            if(!isset(self::$schemas[$s]) && !Cache::get('schema/'.$s)) {
                App::$onEnd['schema:'.$s]=array(
                    'class'=>'Birds\Schema',
                    'method'=>'assign',
                    'params'=>array($s, $cn),
                );
            }
        } else {
            $s = bird::encrypt($cn, 'uuid');
            $p['itemtype']=self::$defaultItemType;
        }
        if(!isset(self::$schemas[$s])) {
            self::$schemas[$s] = $p['itemtype'];
        }
        if($id && self::$cms) {
            $p['itemid'] = self::$cms.'/'.$s.'/'.bird::encrypt($id, 'uuid');
        }
        unset($s);

        if(substr($p['itemtype'], 0, 4)!='http') {
            $p['itemtype'] = 'http://schema.org/'.$p['itemtype'];
        }

        return $p;
    }

    public static function signature($cn, $id=null, $c=null, $divid=null)
    {
        if(is_null(self::$cms)) {
            self::$cms = \Birds\bird::app()->Birds['cms'];
        }
        if(!self::$cms) return array();
        if($cn=='text/html') {
            $cn = 'Birds\\App\\Route';
            $id = App\Route::$current;
        } else if(is_object($cn)) {
            if(!$id) {
                // get id of object
            }
            if(!$c) $c = $cn;
            $cn = get_class($cn);

        }
        return new Node(array(
            'node'=>'div',
            'attributes'=>self::attributes($cn, $id),
            'id'=>$divid,
            'content'=>$c,
        ));
        /*
        $s = '<div'.(($divid)?(' id="'.$divid.'"'):('')).' itemscope';
        foreach(self::attributes($cn, $id) as $k=>$v) {
            $s .= " {$k}=\"{$v}\"";
            unset($k, $v);
        }
        $s .= '>'.$c.'</div>';
        return $s;
        */
    }

    public static function assign($itemtype, $cn)
    {
        $itemtype = preg_replace('/[^a-z0-9_]+/i', '-', $itemtype);
        if($y=Cache::get('schema/'.$itemtype)){
            unset($y);
            return;
        }
        $f = \bird::isWritable(bird::app()->Birds['schema-dir'], $itemtype.'.yml');
        if($f) {
            if(!file_exists($f)) {
                Yaml::save($f, array('class'=>$cn));
            }
            Cache::set('schema/'.$itemtype, $f);
            return;
        } else {
            unset($f);
            return false;
        }
    }

    public static function load($s, $build=false, $prop=null)
    {
        if(!isset(self::$instances[$s])) {
            $cn = get_called_class();
            if(($f=Cache::get('schema/'.$s)) && file_exists($f)) {
                self::$instances[$s] = Yaml::read($f, null, null, $cn);
                unset($f);
            } else if(class_exists($s)) {
                $sc = (property_exists($s, 'schemaid'))?($s::$schemaid):(str_replace(array('\\', '/'), '.', $s));
                $d = bird::app()->Birds['schema-dir'];
                $f = \bird::isReadable($d, $sc.'.yml');
                \bird::log(__METHOD__.','.__LINE__.': '.$f, $d, $sc);
                if($f) {
                    Cache::set('schema/'.$s, $f);
                    self::$instances[$s] = Yaml::read($f, null, null, $cn);
                } else if($build && ($f=\bird::isWritable($d, $sc.'.yml')) && ($r=Schema\Builder::load($sc, $f))) {
                    Cache::set('schema/'.$s, $f);
                    self::$instances[$s] = $r;
                }
                unset($sc, $d, $f);
            }
            if(!isset(self::$instances[$s])) {
                \bird::log("Could not load schema for {$s}, sending a blank one");
                self::$instances[$s] = new Schema();
            // prevent memory leaks?
            /*
            } else {
                if(self::$instances[$s]->instance && self::$instances[$s]->instance!=$s && isset(self::$instances[self::$instances[$s]->instance])) {
                    unset(self::$instances[self::$instances[$s]->instance]);
                }
            */
            }
            self::$instances[$s]->instance = $s;
        }
        if(!is_null($prop)) {
            return (isset(self::$instances[$s]->$prop))?(self::$instances[$s]->$prop):(null);
        }
        return self::$instances[$s];
    }

    public function unload($s)
    {
        if(isset(self::$instances[$s])) {
            unset(self::$instances[$s]);
            return true;
        }
        return false;
    }
    /*
    public function __destruct()
    {
        self::unload($this->instance);
    }
    */
}