<?php
/**
 * App layout engine
 *
 * This package implements layout through classes
 *
 * PHP version 5.3
 *
 * @category  App
 * @package   Birds
 * @author    Guilherme Capilé <capile@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @version   SVN: $Id$
 * @link      http://tecnodz.com/
 */

/**
 * App layout engine
 *
 * This package implements layout through classes
 *
 * @category  App
 * @package   Birds
 * @author    Guilherme Capilé <capile@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @link      http://tecnodz.com/
 */
namespace Birds\App;
class Layout
{
    public static 
        $default='/default.yml',
        $vars=array('$BIRD_TITLE'=>'', '$BIRD_VERSION'=>BIRD_VERSION, '$BIRD_ENV'=>'');
    public $formats=array('text/html'),
        $meta,
        $slots,
        $content,
        $openTags=array(),
        $closeTags=array(),
        $bodyElements=array(),
        $jsOnTop=false;
    /**
     * Layout builder
     * 
     * Creates a new instance of the layout for rendering.
     */
    public function __construct($o=array())
    {
        if(isset($o['options']) && is_array($o['options'])) {
            $o += $o['options'];
            unset($o['options']);
        }
        foreach($o as $n=>$v) {
            //if($n=='meta' || $n=='slots' || $n=='content') continue;
            $n = \Birds\bird::camelize($n);
            if(property_exists($this, $n)) $this->$n = $v;
            unset($o[$n], $n, $v);
        }
        unset($o);
        self::$vars['$BIRD_ENV']=(\bird::env()=='dev')?('dev,'.date('YmdHis')):(\bird::env());
    }

    public function __wakeup()
    {
        self::$vars['$BIRD_ENV']=(\bird::env()=='dev')?('dev,'.date('YmdHis')):(\bird::env());
    }
    
    /**
     * Finds or crestes current instance of the Layout
     */
    public static function find($l)
    {
        if(!$l) $l = self::$default;
        if(strpos($l, '/')===false && class_exists($l)) {
            return new $l();
        }
        if(!file_exists($l)) {
            $ld = \Birds\bird::app()->Birds['layout-dir'];
            if(is_array($ld)) {
                foreach($ld as $dir) {
                    if(file_exists($f=$dir.'/'.$l)) {
                        unset($dir);
                        break;
                    }
                    unset($dir, $f);
                }
            } else if(file_exists($f=$ld.'/'.$l)) {
            } else {
                unset($f);
            }
            if(!isset($f)) {
                return new Layout();
            }
            unset($ld);
            $l = $f;
            unset($f);
        }
        if(substr($l, -4)=='.yml') {
            return \Birds\Yaml::read($l, 3600, null, '\\Birds\\App\\Layout');
        }
        $r = unserialize(file_get_contents($l));
        if($r && is_object($r)) {
            return $r;
        } else {
            return new Layout($r);
        }
    }
}
