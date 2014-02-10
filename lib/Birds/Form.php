<?php
/**
 * Form
 *
 * PHP version 5.3
 *
 * @category  Form
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
 * @category  Form
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2014 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @link      http://tecnodz.com/
 */
namespace Birds;
class Form extends Node
{
    const ATTRIBUTES=' id class name action ';
    public static $base=array();
    public $node='form', $type, $id, $name, $block, $label, $multiple, $min, $max, $bind, $length, $regex, $required, $depends, $choices, $filter, $value;
    /**
     * Form constructor
     */
    public function __construct($d, $o=null, $F=null)
    {
        if(is_null($F)) $F=$this;
        $cn = get_called_class();
        if(!is_array($d)) {
            $d = $cn::$base;
        } else {
            $d += $cn::$base;
        }
        foreach($d as $k=>$v) {
            if(is_int($k)) {
                $this->addItem($v);
            } else if(property_exists($cn, $k)) {
                if(method_exists($cn, $m='set'.ucfirst($k))) {
                    $this->$m($v, $F);
                } else {
                    $this->$k = $v;
                }
            }
            unset($k, $v, $m);
        }
        unset($cn);
        if($o) {
            $this->setValue($o, $F);
        }
        unset($F);
    }

    public function setName($v)
    {
        $this->name = $v;
    }

    public function setBind($v)
    {
        $this->bind = $v;
    }

    public function addItem($v, $k=null, $F=null) {
        if(!$v && (!$k || is_int($k))) return false;
        if(!is_array($v) && !is_object($v)) $v=array('content'=>$v);
        if(!isset($this->items))
            $this->items = array();
        if(!is_int($k)) {
            $v['id'] = $k;
        }
        if(isset($v['type'])) {
            $cn = bird::camelize($v['type'], true);
            if(file_exists($f=dirname(__FILE__).'/Form/'.$cn.'.php')) {
                $cn = 'Birds\\Form\\'.$cn;
                require_once $f;
            } else if(class_exists($v['type'])) {
                $cn = $v['type'];
            } else {
                unset($cn);
            }
        }
        if(!isset($cn)) {
            $cn = 'Birds\\Form\\Input';
        }
        $this->items[] = $cn::create($v, null, $F);
        unset($cn, $k, $v);
        return $F;
    }

    public function setValue($v)
    {
        $this->value = $v;
    }

    /**
     * Creates and returns a new Form object
     *
     * @param $o (mixed)    can be an object/className (in which case the Schema will be searched for),
     *                      or a form definition (array)
     * @param $d (array)    array of values to be applied/validated
     *
     * @returns Birds\Form object
     */
    public static function create($o, $d=null, $F=null)
    {
        if(!is_array($o)) {
            $sn = (is_object($o))?(get_class($o)):($sn);
            $ff = array(str_replace(array('\\', '/'), '.', $sn).'-form.yml');
            if(property_exists($sn, 'schemaid')) {
                array_unshift($ff, $sn::$schemaid.'-form.yml');
            }
            $fn = \bird::file(\bird::app()->Birds['schema-dir'], $ff);
            if(!$fn) \bird::debug('make form for '.$sn);

            unset($ff);
            $f = Yaml::read($fn);
            if(is_object($o)) $f['bind'] = $o;
        } else {
            $f = $o;
            $o = $d;
        }
        $cn = get_called_class();

        return new $cn($f, $o, $F);
    }
}