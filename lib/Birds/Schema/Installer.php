<?php
/**
 * Birds Schema Installer
 *
 * This package provides means to reverse engineer databases as schemas.
 *
 * PHP version 5.3
 *
 * @category  Schema
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2014 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @version   SVN: $Id$
 * @link      http://tecnodz.com/
 */
namespace Birds\Schema;
class Installer
{
    public static function install()
    {
        $r = \Birds\App::request();
        $o=array(
            'lib-dir'=>false,
            'site'=>false,
            'connection'=>false,
            'table'=>array(),
        );
        foreach($r['argv'] as $p) {
            if(preg_match('/^\-\-([a-z\-]+)=(.+)/', $p, $m)) {
                if(substr($m[2], 0, 1)=='"' || substr($m[2], 0, 1)=="'") {
                    $m[2] = substr($m[2], 1, strlen($m[2])-2);
                }
                if(!isset($o[$m[1]])) {
                    \Birds\App::output("\n  Unknown parameter: {$m[1]}\n");
                } else if(!$m[2]) {
                    \Birds\App::output("\n  Parameter {$m[1]} should not be empty.\n");
                } else if(is_array($o[$m[1]])) {
                    $o[$m[1]][] = $m[2];
                } else {
                    if($o[$m[1]]!==false) {
                        \Birds\App::output("\n  Overwriting {$m[1]} with {$m[2]}.\n");
                    }
                    $o[$m[1]] = $m[2];
                }
            } else {
                $o['table'][] = $p;
            }
        }
        $installed=0;
        try {
            if(!isset($o['table'][0])) {
                if(!$o['connection']) {
                    $dbs = \bird::app()->Data;
                    foreach($dbs as $n=>$a) {
                        $o['table'] = array_merge($o['table'], \Birds\Data::connect($n, $a)->getTables());
                        unset($n, $a);
                    }
                    unset($dbs);
                } else {
                    $o['table'] = \Birds\Data::connect($o['connection'])->getTables();
                }
            }
            foreach($o['table'] as $tn) {
                $installed++;
                \Birds\Schema\Builder::load($tn, true);
            }
        } catch(Exception $e) {
            \Birds\App::output($e->getMessage());
        }

        if(!$installed) {
            \Birds\App::output("  Nothing done.\n");
        }
        /*

        chdir(self::$apps);
        exec('git init && git add . && git commit -a -m "Birds installation"');
        */

        \Birds\App::output("\nChecked {$installed} tables in ".\bird::number(microtime(true)-BIRD_TIME,9)."s\n");
    }

}

