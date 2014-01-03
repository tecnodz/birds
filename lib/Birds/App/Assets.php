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
                if(is_dir($f)) {
                    if(file_exists($f=$f.'/index.'.Route::mimeType($format))) {
                        break;
                    }
                } else {
                    if($p=strrpos($url, '.')) {
                        $format = Route::mimeType(substr($url, $p+1));
                    }
                    break;
                }
            }
            unset($d, $f);
        }
        unset($url, $cd);
        if(isset($f)) {
            // optimize
            // add headers
            self::download($f, $format, null, 0, false, false, false);
            \Birds\App::end();
        } else {
            throw new \Birds\App\HttpException(404);
        }
    }

    /**
     * Outputs Bird resources (files located at Birds\App\Resources)
     * and writes the combined output at the document-root
     */
    public static function renderResource($format=null)
    {
        $f = '/'.implode('/', \bird::urlParam());
        if(preg_match('/\.([a-z]{2,6})$/i', $f, $m)) {
            $ext = strtolower($m[1]);
            $format = Route::mimeType($ext);
            unset($m);
        } else {
            $ext = Route::mimeType($format);
        }
        $root = BIRD_ROOT.'/lib/Birds/App/Resources/'.$ext;
        $sf = array($root.$f);
        if(!file_exists($sf[0])) {
            throw new HttpException(404);
        }
        $df = \bird::app()->Birds['document-root'].\bird::scriptName(true);
        if(!is_writable($df) && !is_writable(dirname($df))) {
            $df = \Birds\Cache\File::cacheDir().'/web'.\bird::scriptName(true);
            if(!is_dir(dirname($df))) {
                mkdir(dirname($df), 0777, true);
            }
        }
        unset($f);
        $lmod = filemtime($sf[0]);
        $req = \Birds\App::request();
        if(isset($req['query-string']) && $req['query-string']!='' && preg_match('#^[/a-z0-9\-\_\,]+$#i', $req['query-string'])) {
            foreach(preg_split('#,#', $req['query-string'], null, PREG_SPLIT_NO_EMPTY) as $f) {
                if(file_exists($f=$root.'/'.$f.'.'.$ext)) {
                    $mod=filemtime($f);
                    if($mod > $lmod) {
                        $lmod = $mod;
                    }
                    $sf[] = $f;
                }
                unset($f, $mod);
            }
        }
        if(count($sf)>0) {
            $df = dirname($df).'/'.md5(\Birds\bird::site().':'.implode(':', $sf)).'.'.$ext;
        }
        if(!file_exists($df) || $lmod > filemtime($df)) {
            Assets::combine($sf, $df, $ext);
        }
        //\Birds\App::header('Content-Type: '.$format);
        //\Birds\App::outputFile($df);
        Assets::download($df, $format, null, 0, false, false, true);
        \Birds\App::end();
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
            unset($f);
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
        } else {
            unset($f);
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
                        self::combine(array_keys(${$type}), $fn, $type, $compress);
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
                        \bird::log(ini_set('memory_limit', '8M'));
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
            if($exit) {
                \Birds\bird::end();
            } else {
                return false;
            }
        }
        $expires = 86400 * 10;
        if (isset($_GET['t']))
            $expires = 86400;
        $lastmod = filemtime($file);

        if (!$format) {
            $format = Route::mimeType($extension);
        }

        $gzip = false;
        if($format=='application/javascript' || substr($format, 0, 5) == 'text/') {
            @header('Content-Type: ' . $format.';charset=utf-8');
            @header('Vary: Accept-Encoding', false);
            if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
                $gzip = true;
            }
        } else {
            @header('Content-Type: ' . $format);
        }
        if ($nocache) {
            @header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
            @header('Expires: Thu, 11 Oct 2007 05:00:00 GMT'); // Date in the past
        } else {
            \Birds\bird::getBrowserCache(md5_file($file) . (($gzip) ? (';gzip') : ('')), $lastmod, $expires);
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
            if (!file_exists($gzf) || filemtime($gzf) < $lastmod) {                
                $s = file_get_contents($file);
                $gz = gzencode($s, 9);
                \Birds\bird::save($gzf, $gz, true);
            }
            $gze = 'gzip';
            if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false)
                $gze = 'x-gzip';
            @header('Content-Encoding: ' . $gze);
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
            @header('Accept-Ranges: bytes');
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
        @header('Content-Length: ' . ($seek_end - $seek_start + 1));

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
        $fp = null;
        if($exit) {
            \Birds\bird::end();
        }
    }

}


if(!function_exists('less_dechex')) {
    function less_dechex($a){
        return dechex($a[1]);
    }
}