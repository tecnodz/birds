<?php
/**
 * E-studio Content Managemt System
 *
 * PHP version 5.3
 *
 * @category  Estudio
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2014 Tecnodesign
 * @license   not defined
 * @version   SVN: $Id$
 * @link      https://tecnodz.com/
 */
class Estudio
{
    public static $defaultAction='__/page', $arg;
    protected static $a=array('q'=>'query');

    public static function render($format='text/html')
    {
        \Birds\Schema::$cms = false;
        $valid = \Birds\App\Credential::check(get_called_class(), null, 2);
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
                    ), false).';bird.load("e-studio'.$qs.'");', array('Content-Type: '.$format.';charset=utf8'));
                    //\bird::output('Modernizr.load([{test:window.jQuery,nope:"/_/js/jquery.js"},{load:"'.$base.'/bird.js?Cms",complete:function(){bird.ready()}}]);', array('Content-Type: '.$format.';charset=utf8'));
                } else {
                    \bird::output('window.eStudio=false;', array('Content-Type: '.$format.';charset=utf8'));
                }
            }
            if($valid!==true) {
                throw new \Birds\App\HttpException(403);
            }
            return \Birds\App\Assets::renderResource($format, BIRD_ROOT.'/lib/Estudio/data/web/_');
        }
        if($valid!==true) {
            throw new \Birds\App\HttpException(403);
        }

        $p =\bird::urlParam();
        if(count($p)==1 && isset(self::$a[$p[0]])) {
            return self::{self::$a[$p[0]]}();
        } else if(count($p)<2) {
            throw new \Birds\App\HttpException(404);
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
            if(!($C=\Birds\Content::load($c))) {
                throw new \Exception('Not enough privileges! Or something got wrong');
            }
            unset($hp, $p);
            \Birds\bird::cacheControl('private, must-revalidate', 30);

            // get form object
            \Birds\Form::$base['block']='div';
            $f = \Birds\Form::create($C);
            if(!$f) {
                throw new \Birds\App\HttpException(404);
            }

            // enable csrf prevention
            // if has post value, and the form id matches (check csrf), validate the contents and update the object
            // save the form (this might be a afterEnd action)
            // return the fresh new content or form
            if(\Birds\App::request('ajax')) {
                \Birds\App::output($f->render($format), true);
            }
            return $f->render($format);


        } catch (\Birds\App\HttpException $e) {
            \Birds\bird::log(__METHOD__.' ('.$e->getLine().'): '.$e->getMessage());
            throw new \Birds\App\HttpException($e->getCode());
        } catch (\Exception $e) {
            \Birds\bird::log(__METHOD__.' ('.$e->getLine().'): '.$e->getMessage());
            throw new \Birds\App\HttpException(500);
        }
    }

    public static function header($format='text/html')
    {
        \Birds\Schema::$cms = false;
        if($format=='text/html')
            return '<h1>Birds</h1>';
    }


    public static function cms($format='text/html')
    {
        try{
            return \EstudioPage::match(\bird::scriptName(true))->render($format);
        } catch(\Birds\App\HttpException $e) {
            \bird::app()->error($e->getCode(), $format);
        } catch(Exception $e) {
            \bird::debug(__METHOD__."\n".$e);
        }
    }

    public static function query()
    {
        $qs = \Birds\App::request('query-string');
        $r = array('q'=>($p=strpos($qs, '&'))?(urldecode(substr($qs, 0, $p))):(urldecode($qs)));

        self::$arg = \bird::slug($r['q'], '?');

        if(!($action=self::alias(self::$arg, '__/'))) $action = self::$defaultAction;
        if($action && ($R=Birds\App\Route::find($action, false, false))) {
            if(Birds\App::request('ajax')) {
                $r['r'] = $R->getContent('r-body-0');
            } else {
                $R->render('text/html');
                Birds\App::$running = false;
                Birds\App::end();
            }
        }
        //\bird::debug($s, var_export(Birds\App::request(), true), var_export($R, true));

        \bird::output(json_encode($r), array('Content-Type'=>'application/json'));
    }

    public static function alias(&$s, $prefix=null, $suffix='.txt')
    {
        static $a;
        if(is_null($a)) $a = \bird::app()->Birds['alias-dir'];

        $p=strlen($s);
        while($p>0) {
            if($f=bird::file($a, $prefix.substr($s, 0, $p).$suffix)) {
                break;
            }
            unset($f);
            $p = strrpos(substr($s, 0, $p), '-');
            if(!$p) break;
        }
        if($f) {
            $s = substr($s, $p+1);
            $f = trim(array_shift(file($f)));
        }
        return $f;
    }

}

