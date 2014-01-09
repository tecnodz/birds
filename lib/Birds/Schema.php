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
class Schema {

    public static $defaultItemType='Dataset', $cms, $schemas=array();

    public function __toString()
    {
        bird::debug((array)$this);
    }

    public static function create($cn, $id=null)
    {
        bird::log(__METHOD__, func_get_args());
    }

    public static function attributes($cn, $id=null)
    {
        if(property_exists($cn, 'itemtype')) {
            $p = array('itemtype'=>$cn::$itemtype);
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
            $p = array('itemtype'=>self::$defaultItemType);
        }
        if(!isset(self::$schemas[$cn])) {
            self::$schemas[$cn] = $p['itemtype'];
        }
        if(is_null(self::$cms)) {
            self::$cms = \Birds\bird::app()->Birds['cms'];
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

    public static function signature($cn, $id=null, $c='Birds', $divid='')
    {
        if($cn=='text/html') {
            $cn = 'Birds\\App\\Route';
            $id = App\Route::$current;
        } else if(is_object($cn)) {
            if(!$id) {
                // get id of object
            }
            $cn = get_class($cn);
        }
        $s = '<div'.(($divid)?(' id="'.$divid.'"'):('')).' itemscope';
        foreach(self::attributes($cn, $id) as $k=>$v) {
            $s .= " {$k}=\"{$v}\"";
            unset($k, $v);
        }
        $s .= '>'.$c.'</div>';
        return $s;
    }

    public static function assign($itemtype, $cn)
    {
        \bird::log(__METHOD__, func_get_args());
    }

}