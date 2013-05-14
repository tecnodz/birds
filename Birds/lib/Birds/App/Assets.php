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
        $assetsDir,
        $paths=array(
            'cat'=>'/bin/cat',
            'java'=>'/usr/bin/java',
        );
    /**
     * Outputs the file at given location
     */
    public static function render($format=null)
    {
        $url = \Birds\bird::scriptName(true);
        $cd  = \Birds\bird::app()->Birds['routes-dir'];
        if(is_array($cd)) {
            array_unshift($cd, \Birds\bird::app()->Birds['document-root']);
        } else {
            $cd = array(\Birds\bird::app()->Birds['document-root'], $cd);
        }
        foreach($cd as $d) {
            // todo: search for urlparameters in file name, like used for optimizing images
            if(file_exists($f=$d.$url)) {
                break;
            }
            unset($d, $f);
        }
        unset($url, $cd);
        if(isset($f)) {
            // optimize
            // add headers

            \Birds\bird::download($f, $format);
        } else {
            throw new \Birds\App\HttpException(404);
        }
    }

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
        } else if(file_exists($f=\Birds\bird::app()->Birds['document-root'].$url)) {
        } else if(is_array($d=\Birds\bird::app()->Birds['routes-dir'])) {
            foreach($d as $dn) {
                if(file_exists($f=$dn.$url)) {
                    unset($dn);
                    break;
                }
                unset($dn, $f);
            }
            unset($d);
        } else if(file_exists($f=$d.$url)) {
            unset($d);
        }

        if(isset($f)) {
            return $f;
        }

        return false;
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
        $r='';$css=array();$js=array();$jsn='';$cssn='';
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
                    $r .= '<link rel="stylesheet" type="text/css" href="'.bird::xml($f).'" />';
                }
                unset($m, $fn);
            } else if(preg_match('/\.js(\?.*)?$/i', $f, $m)) {
                if((!isset($m[1]) || $m[1]=='') && $combine && ($fn=self::file($f))) {
                    $js[$fn] = filemtime($fn);
                    $jsn.=':'.$f;
                } else {
                    $r .= '<script src="'.bird::xml($f).'"></script>';
                }
                unset($m);
            }
            unset($f);
        }
        if($combine) {
            $time=date('YmdHis');
            foreach(array('js', 'css') as $type) {
                if(${$type.'n'}) {
                    $f = md5(\Birds\bird::site().${$type.'n'}).'.'.$type;
                    $fn = self::file(self::$assetsUrl.'/'.$f);
                    if(!$fn || filemtime($fn)<max(${$type})) { // generate
                        if(!$fn) {
                            if(!is_writable($fn=\Birds\bird::app()->Birds['document-root'].self::$assetsUrl.'/'.$f) && !is_writable(dirname($fn))) {
                                $cd  = \Birds\bird::app()->Birds['routes-dir'];
                                if(is_array($cd)) {
                                    foreach($cd as $d) {
                                        if(is_dir($d.self::$assetsUrl) && is_writable($fn=$d.self::$assetsUrl.'/'.$f)) {
                                            unset($d);
                                            break;
                                        }
                                        unset($d);
                                    }
                                } else {
                                    $fn = $cd.self::$assetsUrl.'/'.$f;
                                }
                                unset($cd);
                            }
                        }
                        $fs=array_keys(${$type});
                        if($type=='css') {
                            foreach($fs as $i=>$cf) {
                                if(substr($cf, -5)=='.less') {
                                    if(!isset($lc)) {
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
                        }
                        if($compress){
                            // try yui compressor
                            $cmd = self::$paths['cat'].' '.implode(' ',$fs).' | '.self::$paths['java'].' -jar '.BIRD_ROOT.'/lib/yui/yuicompressor.jar --type '.$type.' -o '.$fn;
                            exec($cmd, $output, $ret);
                            if(!$ret) {
                                // $fn was minified -- no need to make it manually
                                $combine = false;
                                chmod($fn, 0666);
                            }
                        }
                        if($combine){
                            foreach($fs as $i=>$fname) {
                                if($i > 0) {
                                    copy($fname, $fn);
                                } else {
                                    file_put_contents($fn, file_get_contents($fname),  FILE_APPEND |  LOCK_EX );
                                }
                                unset($fname, $i);
                            }
                            chmod($fn, 0666);
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

    /**
     * File downloader with support for HTTP 1.1
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


if(!function_exists('less_dechex')) {
    function less_dechex($a){
        return dechex($a[1]);
    }
}