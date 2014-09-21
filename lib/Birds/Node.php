<?php
/**
 * Html\Element
 *
 * This is the base class for rendering HTML elements
 *
 * PHP version 5.3
 *
 * @category  Html
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
 * @category  Html
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2014 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @link      http://tecnodz.com/
 */
namespace Birds;
class Node
{
    const 
        STRUCTURE_NODES=' html head body header footer aside nav ',
        BLOCK_NODES=' div p pre blockquote ',
        EMPTY_NODES=' input br hr ',
        ATTRIBUTES=' id class ';
    public $node='div', $id, $class, $before, $after, $prepend, $append, $content, $items, $attributes;

    public function __construct($a, $defaults=null)
    {
        if(is_array($a) || is_object($a)) {
            if(isset($a['node'])) {
                $this->node = $a['node'];
                unset($a['node']);
            }
            if($defaults && isset($defaults[$this->node])) {
                $a += $defaults[$this->node];
            }
            $cn = get_called_class();
            foreach($a as $k=>$v) {
                if(is_int($k)) {
                    $this->addItem($v, $defaults);
                } else if(property_exists($cn, $k)) {
                    if(method_exists($cn, $m='set'.ucfirst($k))) {
                        $this->$m($v, $defaults);
                    } else {
                        $this->$k = $v;
                    }
                }
                unset($k, $v, $m);
            }
            unset($cn);
        } else {
            $this->content = $a;
        }
    }

    public function __toString()
    {
        return $this->render();
    }

    public function setItems($v, $ref=null)
    {
        if(!$v || count($v)==0) {
            return false;
        }
        $this->items = array();
        foreach($v as $k=>$v) {
            $this->addItem($v, $k, $ref);
            unset($k, $v);
        }
    }

    public function addItem($v, $k=null, $ref=null) {
        if(!$v && (!$k || is_int($k))) return false;
        if(!is_array($v) && !is_object($v)) $v=array('content'=>$v);
        if(!isset($this->items))
            $this->items = array();
        if($k && !is_int($k)) {
            // separate node#id
            $v = array_merge(self::getSelector($k), $v);
        }
        return ($this->items[] = self::create($v, $ref));
    }

    public function render()
    {
        if($this->node) {
            return ((isset($this->before))?($this->before):(''))
                . '<'.$this->node.$this->renderAttributes()
                .   ((strpos(self::EMPTY_NODES, " {$this->node} ")!==false)?('/>'):('>'
                    .   ((isset($this->prepend))?($this->prepend):(''))
                    .   $this->renderContent()
                    .   ((isset($this->append))?($this->append):(''))
                    . '</'.$this->node.'>'
                    ))
                . ((isset($this->after))?($this->after):(''));
        } else {
            return ((isset($this->before))?($this->before):(''))
                .  ((isset($this->prepend))?($this->prepend):(''))
                .  $this->renderContent()
                .  ((isset($this->append))?($this->append):(''))
                .  ((isset($this->after))?($this->after):(''));
        }
    }

    public function renderContent()
    {
        if(is_null($this->content)) $this->content='';
        if(isset($this->items)) {
            foreach($this->items as $k=>&$v) {
                $this->content .= $v;
                unset($this->items[$k], $k, $v);
            }
            $this->items = null;
        }
        return $this->content;
    }

    public function renderAttributes()
    {
        $s='';
        foreach($this->getAttributes() as $k=>$v)  {
            if(is_null($v) || $v===false) continue;
            else if($v===true) {
                $s .= ' '.\bird::xml($k);
            } else {
                $s .= ' '.\bird::xml($k).'="'.\bird::xml($v).'"';
            }
            unset($k, $v);
        }
        return $s;
    }

    public function getAttributes()
    {
        if(is_null($this->attributes)) {
            $this->attributes=array();
        }
        foreach(explode(' ', trim(static::ATTRIBUTES)) as $n) {
            if(!isset($this->attributes[$n])) {
                if(method_exists($this, $m='get'.ucfirst($n))) {
                    $this->attributes[$n] = $this->$m();
                } else if(isset($this->$n)) {
                    $this->attributes[$n] = $this->$n;
                }
            }
            unset($n, $m);
        }
        return $this->attributes;
    }
    /**
     * Creates and returns a new Node object
     *
     * @param $o (mixed)    can be an object/className (in which case the Schema will be searched for),
     *                      or a form definition (array)
     * @param $d (array)    array of values to be applied/validated
     *
     * @returns Birds\Form object
     */
    public static function create($o, $ref=null)
    {
        if(is_object($o) && ($o instanceof Node)) {
            return $o;
        }
        $cn = get_called_class();
        return new $cn($o, $ref);
    }

    public static function getSelector($s)
    {
        $a=array();
        if(preg_match('/^([a-z][a-z0-9\-]+)?(#[a-z0-9\-\_]+)?((.[a-z0-9\-\_]+)*)$/i', $s, $m)) {
            if($m[1]) $a['node']=$m[1];
            if($m[2]) $a['id']=substr($m[2],1);
            if($m[3]) $a['class']=trim(str_replace('.', ' ', $m[3]));
        } else {
            $a['content'] = $s;
        }
        return $a;
    }

}