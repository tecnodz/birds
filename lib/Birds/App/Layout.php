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
        $default='/default.yml';
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
        if(is_null($this->meta)) $this->meta=array();
        if(isset($o['meta']) && is_array($o['meta'])) {
            $this->addMeta($o['meta']);
        }
        if(is_null($this->content)) $this->content=array();
        if(isset($o['content']) && is_array($o['content'])) {
            $this->addContent($o['content']);
        }
        if(isset($o['slots']) && is_array($o['slots'])) {
            $this->slots = array_values($o['slots']);
        }
        if(isset($o['options']) && is_array($o['options'])) {
            foreach($o['options'] as $n=>$v) {
                if($n=='meta' || $n=='slots' || $n=='content') continue;
                $n = \Birds\bird::camelize($n);
                if(isset($this->$n)) $this->$n = $v;
                unset($n, $v);
            }
        }
    }

    /**
     * Outputs the layout for the given format (or the first available format)
     */
    public function render($format=null)
    {
        if(!$format) {
            $format = $this->formats[0];
            if(!$format) return false;
        }
        if((($m='render'.ucfirst(\Birds\bird::camelize($format))) && method_exists($this, $m)) || (($m='render'.ucfirst(substr($format, 0, strpos($format, '/')))) && method_exists($this, $m))) {
            return $this->$m($format);
        } else {
            \Birds\bird::log(__METHOD__.': method '.$m.' does not exist!');
        }
        return false;
    }

    /**
     * HTML formatter
     */
    public function renderText($format)
    {
        \Birds\App::header('Content-Type: '.$format.'; charset=UTF-8');

        // prepare contents
        if(is_array($this->content)) {
            foreach($this->content as $slot=>$cs) {
                foreach($cs as $i=>$c) {
                    $r = Content::find($c);
                    if($r) \Birds\App::output($r->render($format));
                    unset($r, $i, $c);
                }
                unset($slot, $cs);
            }
        }

    }
    /**
     * HTML formatter
     */
    public function renderTextHtml()
    {
        \Birds\App::header('Content-Type: text/html; charset=UTF-8');

        // prepare contents
        $content=array();
        if(is_array($this->content)) {
            foreach($this->content as $slot=>$cs) {
                foreach($cs as $i=>$c) {
                    $r = Content::find($c);
                    if($r) $content[$slot][] = $r;
                    unset($r, $i, $c);
                }
                unset($slot, $cs);
            }
        }

        \Birds\App::output(((isset($this->openTags['html']))?($this->openTags['html']):('<html>'))
            . ((isset($this->openTags['head']))?($this->openTags['head']):('<head>')));
        if(is_array($this->meta)) {
            foreach($this->meta as $n=>$v) {
                if($n=='header') {
                    \Birds\App::header($v);
                } else if($n=='title') {
                    if(is_array($v)) $v = implode(': ', $v);
                    \Birds\App::output('<title>'.\Birds\bird::xml($v).'</title>');
                } else if($n=='stylesheet') {
                    \Birds\App::output(\Birds\bird::minify($v));
                } else if($n=='script') {
                    if(self::$jsOnTop) \Birds\App::output(Assets::minify($v));
                    else $js = \Birds\bird::minify($v);
                } else {
                    if(substr($n, 0, 1)=='@') $n = substr($n,1);
                    if(is_array($v)) {
                        foreach($v as $vv) {
                            \Birds\App::output('<meta name="'.$n.'" content="'.\Birds\bird::xml($vv).'" />');
                            unset($vv);
                        }
                    } else {
                        \Birds\App::output('<meta name="'.$n.'" content="'.\Birds\bird::xml($v).'" />');
                    }

                }
                unset($n, $v);
            }
        }
        \Birds\App::output(((isset($this->closeTags['head']))?($this->closeTags['head']):('</head>'))
            . ((isset($this->openTags['body']))?($this->openTags['body']):('<body>')));
        $slots = (is_array($this->slots))?($this->slots):(array_keys($this->content));
        foreach($slots as $slot) {
            if(isset($content[$slot])) {
                if(in_array($slot, $this->bodyElements)) {
                    $tags=array((isset($this->openTags[$slot]))?($this->openTags[$slot]):('<'.$slot.'>'), (isset($this->closeTags[$slot]))?($this->closeTags[$slot]):('</'.$slot.'>'));
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
                    } else {
                        $tags=array('<div id="'.$slot.'">', '</div>');
                    }
                }
                if($tags[0]) \Birds\App::output($tags[0]);
                // render content
                foreach($content[$slot] as $i=>$c) {
                    \Birds\App::output($c->render('text/html'));
                    unset($c, $content[$slot][$i], $i);
                }


                if($tags[1]) \Birds\App::output($tags[1]);
            }
            unset($slot, $tags);
        }

        \Birds\App::output(((isset($js))?($js):(''))
            . ((isset($this->closeTags['body']))?($this->closeTags['body']):('</body>'))
            . ((isset($this->closeTags['html']))?($this->closeTags['html']):('</html>'))
        );
    }

    /**
     * Metadata componser: adds new metadata entries without removing existing ones
     */
    public function addMeta($a=array())
    {
        if(!is_array($a)) return false;
        foreach($a as $n=>$v) {
            if(isset($this->meta[$n])) {
                if(!is_array($v)) $v=array($v);
                if(!is_array($this->meta[$n])) $this->meta[$n]=array($this->meta[$n]);
                $this->meta[$n] = array_merge($v, $this->meta[$n]);
            } else {
                $this->meta[$n]=$v;
            }         
            unset($n,$v);
        }
    }

    /**
     * Content componser: adds new content entries without removing existing entries
     */
    public function addContent($a=array())
    {
        if(!is_array($a)) {
            $a = array('body'=>$a);
        }
        foreach($a as $slot=>$cs) {
            if(!isset($this->content[$slot])) $this->content[$slot]=array();
            if(!is_array($cs)) {
                $this->content[$slot][]=$cs;
            } else {
                foreach($cs as $c) {
                    $this->content[$slot][]=$c;
                    unset($c);
                }
            }
            unset($slot,$cs);
        }
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
