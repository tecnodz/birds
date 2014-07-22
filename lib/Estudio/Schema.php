<?php
/**
 * E-studio Content Managemt System: Schema
 *
 * PHP version 5.3
 *
 * @category  Estudio
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2014 Tecnodesign
 * @license   not defined
 * @link      https://tecnodz.com/
 */
namespace Estudio;
class Schema extends \Birds\Schema
{
    public static function review()
    {
    	if(!($s = \Estudio::alias(\Estudio::$arg, 'schema/'))) {
    		$s = \Estudio::$arg;
    		\Estudio::$arg = '';
    	}
    	$S = self::load($s);

        \bird::debug(__METHOD__, array('className'=>$s, 'arg'=>\Estudio::$arg, 'schema'=>$S));
    }

}