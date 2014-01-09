<?php
/**
 * App layout engine
 *
 * This package implements layout through classes
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
 * App layout engine
 *
 * This package implements layout through classes
 *
 * @category  App
 * @package   Birds
 * @author    Guilherme Capilé <capile@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @link      http://tecnodz.com/
 */
namespace Birds\App\Layout;
class Text
{
    public static function render($format='text/html', $route)
    {
        \Birds\App::header('Content-Type: '.$format.'; charset=UTF-8');

        // prepare contents
        if(is_array($route->content)) {
            foreach($route->content as $slot=>$cs) {
                foreach($cs as $i=>$c) {
                    $r = \Birds\App\Content::create($c, $format);
                    if($r) \Birds\App::output($r->render($format));
                    unset($r, $i, $c);
                }
                unset($slot, $cs);
            }
        }
    }
}
