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
class Assets
{
    public static
        $assetsUrl,
        $paths=array(
            'cat'=>'/bin/cat',
            'java'=>'/usr/bin/java',
        );
    /**
     * Outputs the file at given location
     */
    public static function render($format=null)
    {
        $b = \Birds\bird::scriptName();
        $url = \Birds\bird::scriptName(true);
        $urlp = ($url!=$b && substr($url, 0, strlen($b))==$b)?(substr($url, strlen($b))):($url);
        $assetsDir = \Birds\bird::app()->Birds['assets-dir'];
        if(is_array($assetsDir)) {
            foreach($assetsDir as $d) {
                // todo: search for urlparameters in file name, like used for optimizing images
                if(file_exists($d.$urlp)) {
                    $f = $d.$urlp;
                }
                unset($d);
            }
        } else if(file_exists($assetsDir.$urlp)) {
            $f = $assetsDir.$urlp;
        }
        if(isset($f)) {
            // optimize
            // add headers

            \Birds\bird::download($f, $format);
        }
    }

    /**
     * Compress Javascript & CSS
     */
    public static function minify($s, $root=false, $compress=true, $before=true, $raw=false)
    {
        if(!$root) {
            $root = \Birds\bird::app()->Birds['document-root'];
            if(!$root) $root = BIRD_VAR;
        }
        // search for static files to compress
        $types = array(
          'js'=>array('pat'=>'#<script [^>]*src="([^"\?\:]+)"[^>]*>\s*</script>#', 'tpl'=>'<script type="text/javascript" src="[[url]]"></script>'),
          'css'=>array('pat'=>'#<link [^>]*type="text/css"[^>]*href="([^"\?\:]+)"[^>]*>#', 'tpl'=>'<link rel="stylesheet" type="text/css" href="[[url]]" />'),
        );
        if(!is_array($s) && strpos($s, '<')===false) {
            $s = array($s);
        }
        $s0=$s;
        $sa = '';
        if(is_array($s)) {
            $f=$s;
            $s='';
            foreach($f as $i=>$url){
                if(!$url) {
                    unset($f[$i]);
                    continue;
                }
                if($raw) {
                    continue;
                }
                if(strpos($url, '<script')!==false || strpos($url, '<style')!==false) {
                    $sa .= $url;
                    continue;
                }
                if(substr($url, 0, 1)!='/' && strpos($url, ':')===false) {
                    $url = self::$assetsUrl.'/'.$url;
                }
                if(substr($url, -5)=='.less' || substr($url, -4)=='.css' || strpos($url, '.css?')!==false){
                    $tpl = $types['css']['tpl'];
                } else {
                    $tpl = $types['js']['tpl'];
                }
                $s .= str_replace('[[url]]', \Birds\bird::xml($url), $tpl);
                unset($i, $url, $tpl);
            }
        }

        if($compress && !file_exists(self::$paths['java'])) {
            $compress = false;
        }
        foreach($types as $type=>$o) {
            $files=array();
            if($raw) {
                $ext = '.'.$type;
                foreach($f as $i=>$url){
                    if(substr($url, -1 * strlen($ext))==$ext) {
                        if(file_exists($url) && substr($url, 0, strlen($root))==$root) {
                            $files[substr($url, strlen($root))]=filemtime($url);
                        } else if(file_exists($root.$url)) {
                            $files[$url]=filemtime($root.$url);
                        }
                    }
                    unset($i, $url);
                }
            } else {
                $lc=false;
                if(preg_match_all($o['pat'], $s, $m)) {
                    foreach($m[1] as $i=>$url) {
                        if(file_exists($root.$url)) {
                            $css=$root.$url;
                            if(substr($url, -5)=='.less') {
                                $less = $url;
                                $css = $root.$url.'.css';
                                if(!file_exists($css)) $css = BIRD_VAR.'/'.basename($url).'.css';
                                if(!file_exists($css) || filemtime($css) < filemtime($root.$less)) {
                                    // compile less
                                    if(!$lc) {
                                        if(!class_exists('lessc')) require_once BIRD_ROOT.'/lib/lessphp/lessc.inc.php';
                                        $lc = new lessc();
                                        $lc->setVariables(array('assets-url'=>'"'.self::$assetsUrl.'"'));
                                        $lc->setImportDir(array(dirname($root.$less).'/'),$root);
                                        $lc->registerFunction('dechex', function($a){
                                            return dechex($a[1]);
                                        });
                                    }
                                    $lc->checkedCompile($root.$less, $css);
                                }
                                if(file_exists($css)) {
                                    $url = $css;
                                }
                            }
                            $files[$url]=filemtime($css);
                            $s = str_replace($m[0][$i], '', $s);
                        }
                        unset($css, $i, $url);
                    }
                    unset($m, $lc);
                }
            }
            if(count($files)>0) {
                $fname = md5(implode(array_keys($files)));
                $url = self::$assetsUrl.'/'.$fname.'.'.$type;
                $file = (substr($root, 0, strlen(BIRD_ROOT))==BIRD_ROOT)?(BIRD_VAR.'/cache/minify/'.basename($url)):($root.$url);
                $time = max($files);
                $build = (!file_exists($file) || filemtime($file) < $time);
                $fs=array_keys($files);
                foreach($fs as $fk=>$fv)
                    if(substr($fv, 0, strlen(self::$assetsUrl))==self::$assetsUrl || file_exists($root.$fv)) $fs[$fk]=$root.$fv;
                if($compress && $build){
                    // try yui compressor
                    $dir = dirname($file);
                    if(!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    $cmd = self::$paths['cat'].' '.implode(' ',$fs).' | '.self::$paths['java'].' -jar '.BIRD_ROOT.'/lib/yui/yuicompressor.jar --type '.$type.' -o '.$file;
                    exec($cmd, $output, $ret);
                    if(!$ret) {
                        $build = false;
                    }
                }
                if($build){
                    $js = '';
                    foreach($fs as $fname => $ftime) {
                        $js .= file_get_contents($fname);
                        unset($fname, $ftime);
                    }
                }
                $url .= '?'.date('YmdHis', $time);
                if($raw) {
                    $s .= ($build)?($js):(file_get_contents($file));
                } else {
                    $s = ($before)?(str_replace('[[url]]', $url, $o['tpl']).$s):($s.str_replace('[[url]]', $url, $o['tpl']));
                }
            }
        }
        $s .= $sa;
        if(!$raw) {
            $s = preg_replace('/>\s+</', '><', trim($s));
        }
        return $s;
    }

    /**
     * File doenloader with support for HTTP 1.1
     */
    public static function download($file, $format=null, $fname=null, $speed=0, $attachment=false, $nocache=false, $exit=true)
    {
        if (connection_status() != 0 || !$file)
            return(false);
        $extension = strtolower(preg_replace('/.*\.([a-z0-9]{1,5})$/i', '$1', basename($file)));

        while (ob_get_level()) {
            ob_end_clean();
        }
        if(!file_exists($file)) {
            if($exit) exit();
            else return false;
        }
        $expires = 3600 * 10;
        if (isset($_GET['t']))
            $expires = 86400;
        $lastmod = filemtime($file);
        if ($format != '')
            @header('Content-Type: ' . $format);
        else {
            //$format = \Birds\bird::fileFormat($file);
            $format = Route::mime($extension);
            if ($format)
                @header('Content-Type: ' . $format);
        }
        $gzip = false;
        if (substr($format, 0, 5) == 'text/' && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip'))
            $gzip = true;
        if (substr($format, 0, 5) == 'text/')
            header('Vary: Accept-Encoding', false);
        if ($nocache) {
            header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
            header('Expires: Thu, 11 Oct 2007 05:00:00 GMT'); // Date in the past
        } else {
            //self::getBrowserCache(md5_file($file) . (($gzip) ? (';gzip') : ('')), $lastmod, $expires);
        }
        @header('Content-Transfer-Encoding: binary');

        if ($attachment) {
            $contentDisposition = 'attachment';
            /* extensions to stream */
            $array_listen = array('mp3', 'm3u', 'm4a', 'mid', 'ogg', 'ra', 'ram', 'wm',
                'wav', 'wma', 'aac', '3gp', 'avi', 'mov', 'mp4', 'mpeg', 'mpg', 'swf', 'wmv', 'divx', 'asf');
            if (in_array($extension, $array_listen))
                $contentDisposition = 'inline';
            if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE")) {
                $fname = preg_replace('/\./', '%2e', $fname, substr_count($fname, '.') - 1);
                @header("Content-Disposition: $contentDisposition;filename=\"$fname\"");
            } else {
                @header("Content-Disposition: $contentDisposition;filename=\"$fname\"");
            }
        }
        if ($gzip) {
            $gzf=BIRD_VAR . '/cache/download/' . md5_file($file);
            if (!file_exists($gzf) || filemtime($gzf) > $lastmod) {                
                $s = file_get_contents($file);
                $gz = gzencode($s, 9);
                \Birds\bird::save($gzf, $gz, true);                
            }
            $gze = 'gzip';
            if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false)
                $gze = 'x-gzip';
            header('Content-Encoding: ' . $gze);
            $file = $gzf;
        }
        $size = filesize($file);
        $range='';
        if(!isset($_SERVER['HTTP_X_REAL_IP'])) {
            //check if http_range is sent by browser (or download manager)
            if (isset($_SERVER['HTTP_RANGE'])) {
                list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);
                if ($size_unit == 'bytes') {
                    //multiple ranges could be specified at the same time, but for simplicity only serve the first range
                    //http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
                    $range = preg_replace('/\,*$/', '', $range_orig);
                    //list($range, $extra_ranges) = explode(',', $range_orig, 2);
                }
            }
            header('Accept-Ranges: bytes');
        }

        //figure out download piece from range (if set)
        if ($range)
            list($seek_start, $seek_end) = explode('-', $range, 2);

        //set start and end based on range (if set), else set defaults
        //also check for invalid ranges.
        $seek_end = (empty($seek_end)) ? ($size - 1) : min(abs(intval($seek_end)), ($size - 1));
        $seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)), 0);

        //Only send partial content header if downloading a piece of the file (IE workaround)
        if ($seek_start > 0 || $seek_end < ($size - 1)) {
            header('HTTP/1.1 206 Partial Content');
            header('Content-Range: bytes ' . $seek_start . '-' . $seek_end . '/' . $size);
        }
        header('Content-Length: ' . ($seek_end - $seek_start + 1));

        //open the file
        $fp = fopen($file, 'rb');
        //seek to start of missing part
        fseek($fp, $seek_start);

        //start buffered download
        $left = $seek_end - $seek_start + 1;
        while ($left>0 && !feof($fp)) {
            //reset time limit for big files
            $chunk = 1024 * 8;
            if ($chunk > $left) {
                $chunk = $left;
                $left = 0;
            }
            $left -= $chunk;
            set_time_limit(0);
            print(fread($fp, $chunk));
            //print(fread($fp, $seek_end - $seek_start + 1));
            flush();
            @ob_flush();
        }

        fclose($fp);
        if($exit) {
            exit;
        }
    }
}
