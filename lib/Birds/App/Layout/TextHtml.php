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
namespace Birds\App\Layout;
class TextHtml
{
    public static function render($format='text/html', $route)
    {
        \Birds\App::header('Content-Type: text/html; charset=UTF-8');
        \Birds\App\Route::$active = $route;

        \Birds\App::$response = new \Birds\Node(array(
            'node'=>'html',
            'items'=>array(
                array('node'=>'head'),
                array('node'=>'body'),
            ),
        ), $route->layout->nodes);

        if(is_array($route->meta) && $route->layout && is_array($route->layout->meta)) {
            $m = \Birds\bird::mergeRecursive($route->meta, $route->layout->meta);
        } else if(is_array($route->meta)) {
            $m = $route->meta;
        } else if($route->layout && is_array($route->layout->meta)) {
            $m = $route->layout->meta;
        } else {
            $m = array();
        }

        $s = array_keys(\Birds\App\Layout::$vars);
        $r = array_values(\Birds\App\Layout::$vars);
        if(is_null(\Birds\Schema::$cms)) {
            \Birds\Schema::$cms = \Birds\bird::app()->Birds['cms'];
        }

        foreach($m as $n=>$v) {
            if($n=='header') {
                \Birds\App::header(str_replace($s, $r, $v));
            } else if($n=='title') {
                if(is_array($v)) $v = implode(': ', $v);
                \Birds\App::$response->items[0]->content .= new \Birds\Node(array('node'=>'title', 'content'=>\Birds\bird::xml(str_replace($s, $r, $v))), $route->layout->nodes);
            } else if($n=='stylesheet') {
                \Birds\App::$response->items[0]->content .= \Birds\bird::minify(str_replace($s, $r, $v)); // function?
            } else if($n=='script') {
                if($route->layout && $route->layout->jsOnTop) {
                    \Birds\App::$response->items[0]->content .= \Birds\bird::minify(str_replace($s, $r, $v));
                } else {
                    \Birds\App::$response->items[1]->append .= \Birds\bird::minify(str_replace($s, $r, $v));
                }
            } else {
                if(substr($n, 0, 1)=='@') $n = substr($n,1);
                if(is_array($v)) {
                    $v = str_replace($s, $r, $v);
                    foreach($v as $vv) {
                        \Birds\App::$response->items[0]->content .= '<meta name="'.$n.'" content="'.\Birds\bird::xml($vv).'" />';
                        unset($vv);
                    }
                } else {
                    \Birds\App::$response->items[0]->content .= '<meta name="'.$n.'" content="'.\Birds\bird::xml(str_replace($s, $r, $v)).'" />';
                }

            }
            unset($n, $v);
        }
        unset($m);

        // prepare contents
        $lc = ($route->layout)?($route->layout->content):(array());
        if($route->content && $lc) {
            $co = $route->content + $lc;
        } else if($route->content) {
            $co = $route->content;
        } else if($lc) {
            $co = $lc;
        } else {
            $co = array();
        }
        // add contents
        $slots = ($route->layout && is_array($route->layout->slots))?($route->layout->slots):(array_keys($co));
        foreach($slots as $slot) {
            if(!isset($co[$slot])) continue; // should add block?
            if($lc && isset($lc[$slot])) {
                $co[$slot] += $lc[$slot];
                ksort($co[$slot]);
            }

            // this should move to Node
            if($route->layout && $route->layout->bodyElements && in_array($slot, $route->layout->bodyElements)) {
                $n = \Birds\App::$response->items[1]
                        ->addItem(array(), $slot, $route->layout->nodes)
                        ->addItem(array(), "div#{$slot}", $route->layout->nodes);
            } else {
                $n = \Birds\App::$response->items[1]->addItem(array(), "div#{$slot}", $route->layout->nodes);
            }

            foreach($co[$slot] as $i=>&$c) {
                $c = \Birds\App\Content::create($c, $format);
                if(\Birds\Schema::$cms) {
                    $cid = \Birds\App\Route::$current.'#'.(($route->content && isset($route->content[$slot][$i]))?("r-{$slot}-{$i}"):("l-{$slot}-{$i}"));
                    $n->addItem(\Birds\Schema::signature('Birds\\App\\Content', $cid, $c));
                } else {
                    $n->addItem(array('content'=>$c));
                }
                unset($i, $c, $cid);
            }
            unset($slot, $cs, $n);
        }
        unset($co, $lc, $slots);

        if(\Birds\Schema::$cms) {
            \Birds\App::$response->items[1]->append .= \Birds\Schema::signature('Birds\\App\\Route', \Birds\App\Route::$current, \bird::$signature, 'bird-signature')
                //. '<script>Modernizr.load([{test:window.jQuery,nope:"'.\Birds\bird::app()->Birds['assets-url'].'/js/jquery.js"},{test:("bird" in window),nope:"'.$cms.'.js?'.\Birds\App\Layout::$vars['$BIRD_ENV'].'",complete:function(){Modernizr.load([{test:window.Bird,yep:["'.$cms.'/bird.js?Cms,'.\Birds\App\Layout::$vars['$BIRD_ENV'].'","/_b/bird-cms.css?'.\Birds\App\Layout::$vars['$BIRD_ENV'].'"],complete:function(){bird.ready()}}])}}]);</script>'
                . '<script>Modernizr.load([{test:("bird" in window),nope:"'.\Birds\Schema::$cms.'.js?'.\Birds\App\Layout::$vars['$BIRD_ENV'].'",complete:function(){Modernizr.load([{test:window.Bird,yep:["'.\Birds\Schema::$cms.'/bird.js?Cms,'.\Birds\App\Layout::$vars['$BIRD_ENV'].'","/_b/bird-cms.css?'.\Birds\App\Layout::$vars['$BIRD_ENV'].'"],complete:function(){if("bird" in window)bird.ready();}}])}}]);</script>'
            ;
        } else {
            \Birds\App::$response->items[1]->append .= '<div id="bird-signature">'.\bird::$signature.'</div>';
        }


        \Birds\App\Route::$active=null;
        unset($route);
        \Birds\App::output(''.\Birds\App::$response);
    }
}

require_once BIRD_ROOT.'/lib/Birds/Schema.php';
require_once BIRD_ROOT.'/lib/Birds/Node.php';

