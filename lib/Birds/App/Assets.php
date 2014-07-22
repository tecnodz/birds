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
        $f = \bird::file($cd, $url);
        //\bird::debug($url, var_export(\Birds\Cache\Wrapper::stat('cache:/web'.$url), true));
        if(!$f && !file_exists($f='cache://web'.$url)) $f = false;
        unset($url, $cd);
        if($f) {
            // todo: search for urlparameters in file name, like used for optimizing images
            if(is_dir($f)) {
                if(!file_exists($f=$f.'/index.'.Route::mimeType($format))) {
                    $f=false;
                }
            } else {
                if($p=strrpos($f, '.')) {
                    $format = Route::mimeType(substr($f, $p+1));
                }
            }
            // optimize
            // add headers
            self::download($f, $format, null, 0, false, false, false);
            \Birds\App::end();
        } else {
            unset($f);
            throw new \Birds\App\HttpException(404);
        }
    }

    /**
     * Outputs Bird resources (files located at Birds\App\Resources)
     * and writes the combined output at the document-root
     */
    public static function renderResource($format=null, $root=null)
    {
        $f = '/'.implode('/', \bird::urlParam());
        if(preg_match('/\.([a-z]{2,6})$/i', $f, $m)) {
            $ext = strtolower($m[1]);
            $format = Route::mimeType($ext);
            unset($m);
        } else {
            $ext = Route::mimeType($format);
        }
        if(!$root) $root = BIRD_ROOT.'/data/web/_/'.$ext;
        else $root.= '/'.$ext;
        $sf = array($root.$f);
        if(!file_exists($sf[0])) {
            throw new HttpException(404);
        }
        $df = \bird::app()->Birds['document-root'].\bird::scriptName(true);
        if(!is_writable($df) && !is_writable(dirname($df))) {
            $df = 'cache://web'.\bird::scriptName(true);
            /*
            $df = \Birds\Cache\File::cacheDir().'/web'.\bird::scriptName(true);
            if(!is_dir(dirname($df))) {
                mkdir(dirname($df), 0777, true);
            }
            */
        }
        unset($f);
        $lmod = filemtime($sf[0]);
        $req = \Birds\App::request();
        if(isset($req['query-string']) && $req['query-string']!='') {
            $q = preg_replace('#[^/a-z0-9\-\_\,]+#i', '', $req['query-string']);
        }
        unset($req);
        if(isset($q)) {
            foreach(preg_split('#,#', $q, null, PREG_SPLIT_NO_EMPTY) as $f) {
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
            Minifier::combine($sf, $df, $ext);
        }
        Assets::download($df, $format, null, 0, false, false, true);
        \Birds\App::end();
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
            \bird::cacheControl('no-cache, no-store, must-revalidate', 0);
        } else {
            \Birds\bird::browserCache($lastmod, $expires);
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
            //$gzf=BIRD_VAR . '/cache/download/' . md5_file($file);
            $gzf = (substr($file, 0, 7)=='cache:/')?($file.'.gz'):('cache://gzip/'.md5($file));
            if (!file_exists($gzf) || filemtime($gzf) < $lastmod) {
                file_put_contents($gzf, gzencode(file_get_contents($file), 9));
                //\Birds\bird::save($gzf, $gz, true);
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
