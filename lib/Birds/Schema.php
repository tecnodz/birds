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

    public static $defaultItemType='Dataset';

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
        } else {
            $p = array('itemtype'=>self::$defaultItemType);
        }
        if($id) {
            $cn = (property_exists($cn, 'schemaid'))?($cn::$schemaid):(bird::encrypt($cn));
            $p['itemid'] = '/_b/'.$cn.'/'.$id;//bird::encrypt($id);
        }

        if(substr($p['itemtype'], 0, 4)!='http') {
            $p['itemtype'] = 'http://schema.org/'.$p['itemtype'];
        }

        return $p;
    }

    public static function signature($cn='text/html', $id=null, $c='Birds')
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
        $s = '<div itemscope';
        foreach(self::attributes($cn, $id) as $k=>$v) {
            $s .= " {$k}=\"{$v}\"";
            unset($k, $v);
        }
        $s .= '>'.$c.'</div>';
        return $s;
    }

}