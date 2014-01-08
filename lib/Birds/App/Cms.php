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
class Cms 
{
    public static function render($format='text/html')
    {
        $valid = Credential::check(get_called_class(), null, 2);
        if($format!='text/html') {
            $base = \bird::scriptName();
            $base = substr($base, 0, strrpos($base, '.'));
            if(!($p=\bird::urlParam())) {
                \bird::cacheControl('private, must-revalidate', 30);
                if(strpos($format, 'javascript') && $valid) {

                    // fill in with user id and credentials
                    \bird::output('window.Bird='.json_encode(array(
                        'cms'=>\Birds\bird::app()->Birds['cms'],
                    ), false).';', array('Content-Type: '.$format.';charset=utf8'));
                    //\bird::output('Modernizr.load([{test:window.jQuery,nope:"/_/js/jquery.js"},{load:"'.$base.'/bird.js?Cms",complete:function(){bird.ready()}}]);', array('Content-Type: '.$format.';charset=utf8'));
                } else {
                    \bird::output('window.Bird=false;', array('Content-Type: '.$format.';charset=utf8'));
                }
            }
            if(!$valid) {
                throw new \Birds\App\HttpException(403);
            }
            return Assets::renderResource($format);
        }
        if(!$valid) {
            throw new \Birds\App\HttpException(403);
        }

        $p =\bird::urlParam();
        return 'ok';
        \bird::debug(func_get_args(), \Birds\App::request(), \bird::scriptName(), $p, \bird::unencrypt($p[1]));
        //\bird::debug(var_export(Credential::check(get_called_class(), null, 6),true));
        return '<p>aaaa</p>';
    }
}