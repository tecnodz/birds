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

        $cms = \Birds\bird::app()->Birds['cms'];

        // prepare contents
        $content=array();
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

        foreach($co as $slot=>$cs) {
            if($lc && isset($lc[$slot])) {
                $cs += $lc[$slot];
                ksort($cs);
            }
            foreach($cs as $i=>$c) {
                $r = \Birds\App\Content::find($c, $format);
                if($r) {
                    $content[$slot][$i] = $r;
                    $content[$slot][$i]->cid = ($route->content && isset($route->content[$slot][$i]))?("r-{$slot}-{$i}"):("l-{$slot}-{$i}");
                }
                unset($r, $i, $c);
            }
            unset($slot, $cs);
        }
        unset($co);

        \Birds\App::output(self::openTag('html', $route->layout).self::openTag('head', $route->layout));

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
        foreach($m as $n=>$v) {
            if($n=='header') {
                \Birds\App::header(str_replace($s, $r, $v));
            } else if($n=='title') {
                if(is_array($v)) $v = implode(': ', $v);
                \Birds\App::output('<title>'.\Birds\bird::xml(str_replace($s, $r, $v)).'</title>');
            } else if($n=='stylesheet') {
                \Birds\App::output(\Birds\bird::minify(str_replace($s, $r, $v)));
            } else if($n=='script') {
                if($route->layout && $route->layout->jsOnTop) \Birds\App::output(Assets::minify(str_replace($s, $r, $v)));
                else $js = \Birds\bird::minify(str_replace($s, $r, $v));
            } else {
                if(substr($n, 0, 1)=='@') $n = substr($n,1);
                if(is_array($v)) {
                    $v = str_replace($s, $r, $v);
                    foreach($v as $vv) {
                        \Birds\App::output('<meta name="'.$n.'" content="'.\Birds\bird::xml($vv).'" />');
                        unset($vv);
                    }
                } else {
                    \Birds\App::output('<meta name="'.$n.'" content="'.\Birds\bird::xml(str_replace($s, $r, $v)).'" />');
                }

            }
            unset($n, $v);
        }
        unset($m);
        \Birds\App::output(self::closeTag('head', $route->layout).self::openTag('body', $route->layout));

        $slots = ($route->layout && is_array($route->layout->slots))?($route->layout->slots):(array_keys($content));

        foreach($slots as $slot) {
            if(isset($content[$slot])) {
                $id=$slot;
                if($route->layout && $route->layout->bodyElements && in_array($slot, $route->layout->bodyElements)) {
                    $tags=array(self::openTag($slot, $route->layout), self::closeTag($slot, $route->layout));
                } else if($slot=='body') {
                    $tags=array('', '');
                } else {
                    // make this logic better, to handle selectors both in $slots and $content
                    if(preg_match('/^([a-z0-9\-\:]+)?(#[^\.]+)?(\..*)?$/i', $slot, $m)) {
                        $el = (isset($m[1]))?($m[1]):('div');
                        $tags=array('<'.$el, '</'.$el.'>');
                        unset($el);
                        if(isset($m[2])) $tags[0] .= ' id="'.substr($m[2],1).'"';
                        if(isset($m[3])) $tags[0] .= ' class="'.trim(str_replace('.', ' ', $m[3])).'"';
                        unset($m, $id);
                    } else {
                        $tags=array('<div id="'.$slot.'">', '</div>');
                    }
                }
                \Birds\App::output('<div'.((isset($id))?(' id="'.\bird::xml($id).'"'):('')).'>'.$tags[0]);
                // render content
                foreach($content[$slot] as $i=>$c) {
                    \Birds\App::output($c->render('text/html'));
                    unset($c, $content[$slot][$i], $i);
                }
                unset($content[$slot], $id);
                \Birds\App::output($tags[1].'</div>');
            }
            unset($slot, $tags);
        }
        unset($content, $s, $r);

        if($cms) {
            \Birds\App::output(
                \Birds\Schema::signature('Birds\\App\\Route', \Birds\App\Route::$current, 'Birds v'.BIRD_VERSION)
                . '<script>Modernizr.load([{test:window.jQuery,nope:"'.\Birds\bird::app()->Birds['assets-url'].'/js/jquery.js"},{test:("bird" in window),nope:"'.$cms.'.js?'.\Birds\App\Layout::$vars['$BIRD_ENV'].'",complete:function(){Modernizr.load([{test:window.Bird,yep:["'.$cms.'/bird.js?Cms,'.\Birds\App\Layout::$vars['$BIRD_ENV'].'","/_b/bird-cms.css?'.\Birds\App\Layout::$vars['$BIRD_ENV'].'"],complete:function(){bird.ready()}}])}}]);</script>'
            );
        }

        \Birds\App::output(((isset($js))?($js):(''))
            . self::closeTag('body', $route->layout)
            . self::closeTag('html', $route->layout)
        );

        \Birds\App\Route::$active=null;
        unset($route);
    }

    public static function openTag($el, $l)
    {
        if($l && isset($l->openTags[$el])) {
            return $l->openTags[$el];
        } else {
            return "<{$el}>";
        }
    }

    public static function closeTag($el, $l)
    {
        if($l && isset($l->closeTags[$el])) {
            return $l->closeTags[$el];
        } else {
            return "</{$el}>";
        }
    }

}
