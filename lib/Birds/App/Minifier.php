<?php
/**
 * Assets optimizer and transformer
 *
 * This package implements javascript/css minifying and image optimizations. Also enables
 * to search for missing assets in $assets-dir
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
 * Assets optimizer and transformer
 *
 * This package implements javascript/css minifying and image optimizations. Also enables
 * to search for missing assets in $assets-dir
 *
 * @category  App
 * @package   Birds
 * @author    Guilherme Capilé <capile@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @link      http://tecnodz.com/
 */
namespace Birds\App;
class Minifier
{
    public static
        $assetsUrl,
        $assetsDir,
        $paths=array(
            'cat'=>'/bin/cat',
            'java'=>'/usr/bin/java',
        );

    public static function file($url, $root=null, $abs=true)
    {
        if($abs && file_exists($url)) {
            $bd = dirname(BIRD_ROOT);
            if(substr($url,0,strlen($bd))==$bd) {
                return $url;
            }
            $abs=false;
        }
        if($root && file_exists($f=$root.$url)) {
        } else {
            $app = \Birds\bird::app()->Birds;
            $a = (is_array($app['routes-dir']))?(array_merge(array($app['document-root']), $app['routes-dir'])):(array($app['document-root'], $app['routes-dir']));
            $f = \bird::file($a, $url, 'r');
            unset($app, $a);
        }
        return $f;
    }

    /**
     * Compress Javascript & CSS
     */
    public static function minify($s, $root=null, $compress=true, $before=true, $raw=false)
    {
        if(is_null(self::$assetsUrl)) self::$assetsUrl = \Birds\bird::app()->Birds['assets-url'];
        // search for static files to compress
        if(!is_array($s)) $s = array($s);
        $combine = ($raw || $compress);
        if($compress && !file_exists(self::$paths['java'])) {
            $compress = false;
        }
        $r='';$css=array();$js=array();$jsn='';$cssn='';//\Birds\Cache::siteKey().':';
        foreach($s as $f) {
            if(strpos($f, '<')!==false) {
                if($combine && preg_match_all('#<script [^>]*src="([^"\?\:]+)"[^>]*>\s*</script>|<link [^>]*type="text/css"[^>]*href="([^"\?\:]+)"[^>]*>#i', $f, $m)) {
                    $fr=array();
                    foreach($m[0] as $i=>$p) {
                        if($m[1][$i] && ($fn=self::file($m[1][$i]))) {
                            $js[$fn] = filemtime($fn);
                            $fr[]=$p;
                            $jsn.=':'.$m[1][$i];
                        } else if($m[2][$i] && ($fn=self::file($m[2][$i]))) {
                            $css[$fn] = filemtime($fn);
                            $fr[]=$p;
                            $cssn.=':'.$m[2][$i];
                        }
                        unset($i, $p, $fn);
                    }
                    if(isset($fr[0])) {
                        $r .= str_replace($fr, '', $f);
                        unset($fr, $f);
                        continue;
                    }
                    unset($fr);
                }
                $r .= $f;
            } else if(preg_match('/\.(css|less)(\?.*)?$/i', $f, $m)) {
                if((!isset($m[2]) || $m[2]=='') && $combine && ($fn=self::file($f))) {
                    $css[$fn] = filemtime($fn);
                    $cssn.=':'.$f;
                } else {
                    $r .= '<link rel="stylesheet" type="text/css" href="'.\Birds\bird::xml($f).'" />';
                }
                unset($m, $fn);
            } else if(preg_match('/\.js(\?.*)?$/i', $f, $m)) {
                if((!isset($m[1]) || $m[1]=='') && $combine && ($fn=self::file($f))) {
                    $js[$fn] = filemtime($fn);
                    $jsn.=':'.$f;
                } else {
                    $r .= '<script src="'.\Birds\bird::xml($f).'"></script>';
                }
                unset($m);
            }
            unset($f);
        }
        if($combine) {
            foreach(array('js', 'css') as $type) {
                if(${$type.'n'}) {
                    $t = max(${$type});
                    $time=date('YmdHis', $t);
                    $f = \bird::hash(\Birds\bird::site().${$type.'n'}).'.'.$type;
                    $fn = self::file(self::$assetsUrl.'/'.$f);
                    if(!$fn || filemtime($fn)<max(${$type})) { // generate
                        if(!$fn) {
                            if (!($fn=\bird::isWritable(\Birds\bird::app()->Birds['document-root'].self::$assetsUrl.'/'.$f))
                             && !($fn=\bird::isWritable(\Birds\bird::app()->Birds['routes-dir'], self::$assetsUrl.'/'.$f))
                            ) {
                                // cannot combine...
                                /*
                                if($type=='css') {
                                    if($before) $r = '<link rel="stylesheet" type="text/css" href="'.self::$assetsUrl.'/'.$f.'?'.$time.'" />'.$r;
                                    else $r .= '<link rel="stylesheet" type="text/css" href="'.self::$assetsUrl.'/'.$f.'?'.$time.'" />';
                                } else {
                                    if($before) $r = '<script src="'.self::$assetsUrl.'/'.$f.'?'.$time.'"></script>'.$r;
                                    else $r .= '<script src="'.self::$assetsUrl.'/'.$f.'?'.$time.'"></script>';
                                }
                                */
                            }
                        }
                        if($fn) {
                            self::combine(array_keys(${$type}), $fn, $type, $compress);
                        }
                    }
                    if($raw) {
                        $r .= file_get_contents($fn);
                    } else  if($type=='css') {
                        if($before) $r = '<link rel="stylesheet" type="text/css" href="'.self::$assetsUrl.'/'.$f.'?'.$time.'" />'.$r;
                        else $r .= '<link rel="stylesheet" type="text/css" href="'.self::$assetsUrl.'/'.$f.'?'.$time.'" />';
                    } else {
                        if($before) $r = '<script src="'.self::$assetsUrl.'/'.$f.'?'.$time.'"></script>'.$r;
                        else $r .= '<script src="'.self::$assetsUrl.'/'.$f.'?'.$time.'"></script>';
                    }
                }
            }
        }
        if(!$raw) {
            $r = preg_replace('/>\s+</', '><', trim($r));
        }
        return $r;
    }


    public static function combine($fs, $fn, $type, $compress=true)
    {
        if(!is_writable($fn) && !is_writable(dirname($fn))) {
            return false;
        }
        if(!is_array($fs)) {
            $fs = array($fs);
        }
        if($type=='css') {
            foreach($fs as $i=>$cf) {
                if(substr($cf, -5)=='.less') {
                    if(!isset($lc)) {
                        ini_set('memory_limit', '8M');
                        if(!class_exists('lessc')) require_once BIRD_ROOT.'/lib/lessphp/lessc.inc.php';
                        $lc = new \lessc();
                        $lc->setVariables(array('assets-url'=>'"'.self::$assetsUrl.'"'));
                        //$lc->setImportDir(array(dirname($root.$less).'/'),$root);
                        $lc->registerFunction('dechex', 'less_dechex');
                    }
                    $lc->checkedCompile($cf, $cf.'.css');
                    if(file_exists($cf.'.css')) {
                        $fs[$i].='.css';
                    }
                }
            }
            unset($lc);
        }
        $combine = true;
        if($compress){
            // try yui compressor
            $tmp = tempnam(dirname($fn), '.'.basename($fn));
            $cmd = self::$paths['cat'].' '.implode(' ',$fs).' | '.self::$paths['java'].' -jar '.BIRD_ROOT.'/lib/yui/yuicompressor.jar --type '.$type.' -o '.$tmp;
            exec($cmd, $output, $ret);
            if(file_exists($tmp)) {
                if(rename($tmp,$fn)) {
                    // $fn was minified -- no need to make it manually
                    chmod($fn, 0666);
                    $combine = false;
                } else {
                    @unlink($tmp);
                }
            }
        }
        if($combine){
            // atomic writes
            $tmp = tempnam(dirname($fn), '.' . basename($fn));

            foreach($fs as $i=>$fname) {
                if($i == 0) {
                    copy($fname, $tmp);
                } else {
                    file_put_contents($tmp, file_get_contents($fname),  FILE_APPEND |  LOCK_EX );
                }
                unset($fname, $i);
            }
            if(rename($tmp, $fn)) {
                chmod($fn, 0666);
            }
        }
        return true;

    }
}


if(!function_exists('less_dechex')) {
    function less_dechex($a){
        return dechex($a[1]);
    }
}