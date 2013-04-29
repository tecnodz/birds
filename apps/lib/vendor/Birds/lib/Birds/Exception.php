<?php
/**
 * Error Handling and exceptions
 *
 * This package implements error handling for the framework
 *
 * PHP version 5.3
 *
 * @category  Exception
 * @package   Birds
 * @author    Guilherme Capilé <capile@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @version   SVN: $Id$
 * @link      http://tecnodz.com/
 */

/**
 * Error Handling and exceptions
 *
 * This package implements error handling for the framework
 *
 * @category  Exception
 * @package   Birds
 * @author    Guilherme Capilé <capile@tecnodz.com>
 * @copyright 2011 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @link      http://tecnodz.com/
 */
namespace Birds;
class Exception extends \Exception
{
    public $error = true;
    public function __construct($message, $code=0, $previous=null)
    {
        if(is_array($message)) {
            $m = array_shift($message);
            $message = vsprintf($m, $message);
        }
        parent::__construct($message);
    }
}
