<?php
/**
 * App content controller
 *
 * This class makes the connection between other classes and the App\Layout->content
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
 * App content controller
 *
 * This class makes the connection between other classes and the App\Layout->content
 *
 * @category  App
 * @package   Birds
 * @author    Guilherme Capilé <capile@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @link      http://tecnodz.com/
 */
namespace Birds\App;
class Content
{
    public static $itemtype='WebPageElement', $schemaid='r';
    public $cid,            # internal content ID
        $class,             # class name where this object is defined
        $uid,               # primary key from $className to search for. If not defined, $method will be called statically.
        $method='render',   # method to use for rendering
        $params,
        $credentials,       # content credentials. If fails, the contents is not shown.
        $content,           # rendered content
        $prepare;           # if true, should be executed before any output is sent

    /**
     * Content builder
     *
     * Content might be available in several formats (as read at the Yaml definition):
     *
     * 1. (string) Document UID
     *    In this case, only lowercase alphanumerical characters and / - . are allowed.
     *    We should search for the content or return false if not found.
     *
     * 2. (string) Simplified ClassName::method/property
     *         ClassName::render (static)
     *         ClassName::$staticVar
     *
     * 3. (string) HTML/Text Content
     *    If the contents is not a valid Document ID, it should be treated as normal content.
     * 
     * 4. (array) Content definition: uses this class properties as values:
     *         class: $cn       # class name where this object is defined
     *         uid: $id         # primary key from $className to search for. If not defined, $method will be called statically.
     *         method: render   # method to use for rendering
     *         params: ~        # params to pass
     *         credentials: ~   # content credentials. If fails, the contents is not shown.
     *         content: ~       # rendered content
     *         prepare: ~       # if true, should be executed before any output is sent
     *  
     */
    public function __construct($o=array())
    {
        if(is_string($o)) {
            $o=self::parseString($o);
        }
        foreach($o as $n=>$v) {
            if(property_exists($this, $n)) $this->$n=$v;
            unset($n, $v);
        }
    }

    public function __toString()
    {
        try {
            return (string) $this->render();
        } catch(\Exception $e) {
            \bird::log(__METHOD__.': '.$e->getMessage());
            return '';
        }
    }

    /**
     * Outputs the layout for the given format (or the first available format)
     */
    public function render($format='text/html')
    {
        if(is_null($this->content)) {
            $this->loadContent($format);
        }
        return $this->content;
    }

    public function loadContent($format='text/html')
    {
        $this->content = self::load($this, $format);
        return $this;
    }

    public static function load($d, $format='text/html')
    {
        if(is_object($d)) $d=(array)$d;
        else if($d && !is_array($d)) $d = self::parseString($d);
        else if(!is_array($d)) return false;
        try {
            if(isset($d['class']) && $d['class']) {
                if(is_object($d['class']) || class_exists('\\'.$d['class'])) {
                    if(is_object($d['class'])) {
                        $cn = $d['class'];
                        unset($d['class']);
                        $static = false;
                    } else {
                        $cn = '\\'.$d['class'];
                        $static = true;
                        if(isset($d['uid'])) {
                            $cn = $cn::find($d['uid']);
                            if(!$cn) return false;
                            $static = false;
                        }
                    }
                    if(isset($d['method']) && $d['method']) {
                        if($d['method'][0]=='$') {
                            return ($static)?($cn::${substr($d['method'],1)}):($cn->{substr($d['method'],1)});
                        } else {
                            return call_user_func_array(array($cn, $d['method']), (isset($d['params']))?($d['params']):(array($format)));
                        }
                    } else if(!$static) {
                        return $cn;
                    } else {
                        unset($cn);
                        return false;
                    }
                }
            } else if(isset($d['uid']) && $d['uid']) { // static content
                $contentDir = \Birds\bird::app()->Birds['content-dir'];
                $found=false;
                $ext = ($format)?(Route::mimeType($format)):('');
                $c = \bird::file(\Birds\bird::app()->Birds['content-dir'], ($ext)?(array($d['uid'], $d['uid'].'.'.$ext)):($d['uid']));
                unset($ext);
                if($c) {
                    return file_get_contents($c);
                }
                unset($c);
            }
            unset($d);
        } catch(Exception $e) {
            \Birds\bird::log(__METHOD__.': '.$e->getMessage());
             return false;
        }
        if(!isset($s)) return false;
        return $s;
    }

    /**
     * Finds or creates current instance of the content
     */
    public static function create($c, $f='text/html', $output=false)
    {
        if(!$c) return false;
        $c = new Content($c);
        // check credentials
        if($output) {
            \Birds\App::output($c->render($f));
            unset($c);
            return true;
        }
        if($c->prepare) {
            $c->render($f);
        }
        return $c;
    }

    public static function parseString($c)
    {
        if(strpos($c, '::') && preg_match('#^([a-z\\][a-z0-9\\\_]+)\:\:(\$?[a-z\\][a-z0-9\\\_]+)#i', $c, $m)) {
            return array('class'=>$m[1], 'method'=>$m[2]);
        } else if(preg_match('#^[/a-z0-9\-\.]+$#i', $c)) {
            return array('uid'=>$c);
        } else {
            return array('content'=>$c);
        }
    }
}
