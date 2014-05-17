<?php
/**
 * Bird Content Managemt System
 *
 * PHP version 5.3
 *
 * @category  App
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @version   SVN: $Id$
 * @link      http://tecnodz.com/
 */

/**
 * Bird Content Managemt System
 *
 * @category  App
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @link      http://tecnodz.com/
 */
namespace Birds\App;
class E 
{
    public static function render($format='text/html')
    {
        \Birds\Schema::$cms = false;
        $valid = Credential::check(get_called_class(), null, 2);
        if($format!='text/html') {
            $base = \bird::scriptName();
            $base = substr($base, 0, strrpos($base, '.'));
            if(!($p=\bird::urlParam())) {
                \bird::cacheControl('private, must-revalidate', 30);
                if(strpos($format, 'javascript') && $valid===true) {

                    // fill in with user id and credentials
                    $qs = (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'])?('?'.preg_replace('/[^a-z0-9\,\.]/', '', $_SERVER['QUERY_STRING'])):('');
                    \bird::output('window.eStudio='.json_encode(array(
                        'cms'=>\Birds\bird::fullUrl(\Birds\bird::app()->Birds['cms']),
                        //'css'=>\Birds\bird::$cssPrefix,
                    ), false).';bird.load("'.\Birds\bird::app()->Birds['cms'].'/e-studio.js'.$qs.'","'.\Birds\bird::app()->Birds['cms'].'/e-studio.css'.$qs.'");', array('Content-Type: '.$format.';charset=utf8'));
                    //\bird::output('Modernizr.load([{test:window.jQuery,nope:"/_/js/jquery.js"},{load:"'.$base.'/bird.js?Cms",complete:function(){bird.ready()}}]);', array('Content-Type: '.$format.';charset=utf8'));
                } else {
                    \bird::output('window.eStudio=false;', array('Content-Type: '.$format.';charset=utf8'));
                }
            }
            if($valid!==true) {
                throw new \Birds\App\HttpException(403);
            }
            return Assets::renderResource($format);
        }
        if($valid!==true) {
            throw new \Birds\App\HttpException(403);
        }

        $p =\bird::urlParam();
        if(count($p)<2) {
            throw new HttpException(404);
        }
        try {
            $c = \Birds\Schema::load($p[0]);
            $uid = \bird::decrypt($p[1], 'uuid');
            if(!$c || !$uid) {
                throw new \Exception('Oops, something got wrong!');
            } else if(!isset($c['class']) || Credential::check($c['class'], $uid, 2)!==true) {
                throw new \Exception('Not enough privileges!');
            }
            $c+=array('method'=>null, 'params'=>null,'uid'=>$uid);
            if(!($C=Content::load($c))) {
                throw new \Exception('Not enough privileges! Or something got wrong');
            }
            unset($hp, $p);
            \Birds\bird::cacheControl('private, must-revalidate', 30);

            // get form object
            \Birds\Form::$base['block']='div';
            $f = \Birds\Form::create($C);
            if(!$f) {
                throw new HttpException(404);
            }

            // enable csrf prevention
            // if has post value, and the form id matches (check csrf), validate the contents and update the object
            // save the form (this might be a afterEnd action)
            // return the fresh new content or form
            if(\Birds\App::request('ajax')) {
                \Birds\App::output($f->render($format), true);
            }
            return $f->render($format);


        } catch (HttpException $e) {
            \Birds\bird::log(__METHOD__.' ('.$e->getLine().'): '.$e->getMessage());
            throw new HttpException($e->getCode());
        } catch (\Exception $e) {
            \Birds\bird::log(__METHOD__.' ('.$e->getLine().'): '.$e->getMessage());
            throw new HttpException(500);
        }
    }

    public static function header($format='text/html')
    {
        \Birds\Schema::$cms = false;
        if($format=='text/html')
            return '<h1>Birds</h1>';
    }
}