<?php
/**
 * Birds Unit Tests
 *
 * This package runs several tests on Birds Framework and any application
 *
 * PHP version 5.3
 *
 * @category  Test
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @version   SVN: $Id$
 * @link      http://tecnodz.com/
 */

/**
 * Birds Unit Tests
 *
 * This package runs several tests on Birds Framework and any application
 *
 * @category  Test
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @link      http://tecnodz.com/
 */
namespace Birds;
class Test
{
    public static $name = 'App\\Test',
        $repeat=0;


    public static function find()
    {
        bird::debug(__METHOD__);
    }
    public static function test()
    {
        if(get_called_class()!='Birds\\Test') return false;
        $d = BIRD_ROOT.'/resources/test';
        if(!in_array($d, bird::$lib)) bird::$lib[]=$d;
        $p = bird::urlParam();
        if($p) {
            $p = array_merge($p, App::request()['argv']);
        } else {
            $p = App::request()['argv'];
        }

        if(!$p || count($p) == 0) {
            $f = glob($d.'/*Test.php');
            $p = array();
            foreach($f as $fn) {
                $p[] = basename($fn, '.php');
                unset($fn);
            }
            unset($f);
        } else {
            foreach($p as $i=>$fn) {
                $cn = ucfirst(bird::camelize($fn)).'Test';
                if(class_exists($cn)) $p[$i]=$cn;
                else unset($p[$i]);
                unset($i, $fn);
            }
        }
        App::output('Starting '.count($p).' tests with peak memory '.bird::bytes(memory_get_peak_usage()).":\n");
        foreach($p as $cn) {
            App::output("\n ".$cn::$name.str_repeat(' ', 99 - strlen($cn::$name)));
            $i = $cn::$repeat;
            $t = microtime(true);
            try {
                while($i>=0) {
                    if(!$cn::test()) {
                        App::output("ERROR\n");
                        break;
                    }
                    $i--;
                }
            } catch(Exception $e) {
                App::output("ERROR\n  ", $e->getMessage());
            }
            if($i==-1) App::output("OK\n");
            App::output(' In: '.bird::number(microtime(true)-$t,9)."s peak memory: ".bird::bytes(memory_get_peak_usage())."\n");
        }
        App::output("\nTests completed in ".bird::number(microtime(true)-BIRD_TIME,9)."s\n");
    }
}

