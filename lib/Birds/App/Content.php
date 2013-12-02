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
    public $class,          # class name where this object is defined
        $uid,               # primary key from $className to search for. If not defined, $method will be called statically.
        $method='render',   # method to use for rendering
        $credentials,       # content credentials. If fails, the contents is not shown.
        $content,           # rendered content
        $prepare;           # if true, should be executed before any output is sent

    /**
     * Content builder
     */
    public function __construct($o=array())
    {
        foreach($o as $n=>$v) {
            if(property_exists($this, $n)) $this->$n=$v;
            unset($n, $v);
        }
    }

    /**
     * Outputs the layout for the given format (or the first available format)
     */
    public function render($format='text/html')
    {
        $wrap=array('', '');
        if(is_null($this->content)) {
            try {
                if($this->class) {
                    if(class_exists('\\'.$this->class)) {
                        $cn = '\\'.$this->class;
                        if($this->uid) {
                            $this->content = $cn::find($this->uid);
                            if($this->content && $this->method) {
                                $m = $this->method;
                                $this->content = $this->content->$m($format);
                                unset($m);
                            }
                        } else if($this->method && method_exists($cn, $this->method)) {
                            $m = $this->method;
                            $this->content = $cn::$m($format);
                        }
                    }
                } else if($this->uid) { // static content
                    $contentDir = \Birds\bird::app()->Birds['content-dir'];
                    $found=false;
                    $ext = ($format)?(Route::mimeType($format)):('');
                    if(is_array($contentDir)) {
                        foreach($contentDir as $dir) {
                            if(file_exists($c=$dir.'/'.$this->uid) || ($ext && file_exists($c=$dir.'/'.$this->uid.'.'.$ext))) {
                                unset($dir);
                                $found=true;
                                break;
                            }
                            unset($dir);
                        }
                    } else if(file_exists($c=$contentDir.'/'.$this->uid) || ($ext && file_exists($c=$contentDir.'/'.$this->uid.'.'.$ext))) {
                        $found=true;
                    }
                    if($format=='text/html') {
                        $wrap[0]='<div data-content="'.\bird::xml($this->uid).'">';
                        $wrap[1]='</div>';
                    }
                    if(!$found) {
                        $this->content=false;
                    } else {
                        // return file_get_contents($c); // output directly?
                        $this->content=file_get_contents($c);
                    }
                    unset($found, $contentDir, $c, $ext);
                }
            } catch(Exception $e) {
                \Birds\bird::log(__METHOD__.': '.$e->getMessage());
                $this->content=false;
            }
        }
        return $wrap[0].$this->content.$wrap[1];
    }


    /**
     * Finds or creates current instance of the content
     *
     * Content might be available in several formats (as read at the Yaml definition):
     *
     * 1. (string) Document UID
     *    In this case, only lowercase alphanumerical characters and / - . are allowed.
     *    We should search for the content or return false if not found.
     *
     * 2. (string) HTML/Text Content
     *    If the contents is not a valid Document ID, it should be treated as normal content.
     * 
     * 3. (array) Content definition: uses this class properties as values:
     *         class: $cn       # class name where this object is defined
     *         uid: $id         # primary key from $className to search for. If not defined, $method will be called statically.
     *         method: render   # method to use for rendering
     *         credentials: ~   # content credentials. If fails, the contents is not shown.
     *         content: ~       # rendered content
     *         prepare: ~       # if true, should be executed before any output is sent
     *
     */
    public static function find($c, $f='text/html')
    {
        if(!$c) return false;
        if(!is_array($c)) {
            if(preg_match('#^[/a-z0-9\-\.]+$#i', $c)) $c = array('uid'=>$c);
            else $c = array('content'=>$c);
        }
        $c = new Content($c);
        // check credentials
        if($c->prepare) $c->render($f);
        return $c;
    }
}
