<?php
/**
 * WebAp packager
 *
 * PHP version 5.3
 *
 * @category  App
 * @package   Birds
 * @author    Guilherme Capilé <capile@tecnodz.com>
 * @copyright 2014 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @version   SVN: $Id$
 * @link      http://tecnodz.com/
 */
namespace Birds\App;
class Webapp extends Assets
{
    static $optimizers = array('text/css'=>'optimizeCss', 'application/javascript'=>'optimize', 'text/html'=>'optimizeHtml');
    public static function find($url, $format=null)
    {
        if($format && isset(static::$optimizers[$format])) {
            return static::{static::$optimizers[$format]}($url, $format);
        }
        return parent::find($url, $format);
    }

    public static function optimizeCss($url, $format)
    {
        if(substr($url, -4)=='.css') {
            $less = parent::find(substr($url, 0, strlen($url)-4).'.less');
            if($less) {
                $css  = parent::find($url);
                if($css && filemtime($css)>filemtime($less)) return $css;
                return static::optimize($url, $format, $less);
            }
        }
        return static::optimize($url);
    }

    public static function optimize($url, $format=null, $f=null)
    {
        if(!$f) $f=parent::find($url);
        if(substr($f, 0, 7)==='cache:/') return $f;
        $format = substr($format, strpos($format, '/')+1);
        $cf = 'cache://web'.$url;
        if(Minifier::combine(array($f), $cf, $format)) {
            return $cf;
        } else {
            return $f;
        }
    }

    public static function optimizeHtml($url, $format=null, $f=null)
    {
        if(!$f) $f=parent::find($url, $format);
        if(substr($f, 0, 7)==='cache:/') return $f;
        $cf = 'cache://web'.$url;
        if(substr($cf, -1)=='/') $cf .= 'index.'.Route::mimeType($format);
        $format = substr($format, strpos($format, '/')+1);
        if(false && file_exists($cf) && filemtime($cf)>filemtime($f)) {
            return $cf;
        }
        $s = trim(preg_replace('/>\s+</', '><', file_get_contents($f)));
        if(strpos($s, '<!DOCTYPE')===false) {
            $s0 = '<!DOCTYPE html><html lang="'.\bird::$lang.'">';
            $s1 = '';
            if(strpos($s, '<html')!==false) {
                $s = preg_replace('#</?html[^>]*>#', '', $s);
            }
            if(strpos($s, '<head')===false) {
                $s0 .= '<head><meta charset="utf-8">';
                if(preg_match('#<title[^>]*>[^<]+</title>#', $s, $m)) {
                    $s = str_replace($m[0], '', $s);
                    $s0 .= $m[0];
                }
                foreach(glob(dirname($f).'/_/*.{css,less,js}',  GLOB_BRACE) as $a) {
                    $time = date('YmdHis', filemtime($a));
                    if(substr($a, -3)=='.js') $s1 .= '<script async src="_/'.basename($a).'?'.$time.'"></script>';
                    else {
                        if(substr($a, -5)=='.less') $s0 .= '<link rel="stylesheet" href="_/'.basename($a, '.less').'.css?'.$time.'" />';
                        else $s0 .= '<link rel="stylesheet" href="_/'.basename($a).'?'.$time.'" />';
                    }
                }
                $s0 .= '</head>';
            }
            if(strpos($s, '<body')===false) {
                $s0 .= '<body><div class="app" data-url="'.\bird::xml($url).'">';
                $s1 .= '</div></body>';
            }
            $s1 .= '</html>';
            $s = $s0.$s.$s1;
        }
        if(\bird::save($cf, $s)) return $cf;
        else return $f;
    }


}
