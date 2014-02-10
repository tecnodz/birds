<?php
/**
 * YAML loading and deploying using Spyc
 *
 * This package implements file caching and a interface to Spyc (www.yaml.org)
 *
 * PHP version 5.3
 *
 * @category  Yaml
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @version   SVN: $Id$
 * @link      http://tecnodz.com/
 */

/**
 * YAML loading and deploying using Spyc
 *
 * This package implements file caching and a interface to Spyc (www.yaml.org)
 *
 * @category  Yaml
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @link      http://tecnodz.com/
 */
namespace Birds;
class Yaml
{
    /**
     * Defines/sets current Yaml parser according to self::$parsers
     */
    protected static $parser;
    public static function parser()
    {
        if(is_null(self::$parser)) {
            if(function_exists('yaml_parse')) self::$parser='php-yaml';
            else self::$parser = 'Spyc';
        }
        return self::$parser;
    }

    /**
     * Loads YAML file and converts to a PHP array
     *
     * @param string $s file name to load
     * 
     * @return array contents of the YAML text
     */
    public static function read($s, $timeout=3600, $filter=null, $cn=null)
    {
        if(!is_string($s) || !file_exists($s) || filesize($s)<2) return false;
        $readTimeout = filemtime($s);
        $ckey = 'yaml/'.(($cn)?(str_replace('\\', '_', $cn).'-'):('')).md5($s.((is_array($filter))?(':'.serialize($filter)):('')));
        $a = Cache::get($ckey, $readTimeout);
        if ($a) {
            unset($readTimeout, $ckey);
            return $a;
        }
        $parser = self::parser();
        if($parser=='php-yaml') {
            if($filter) {
                $all = yaml_parse_file($s, -1, $num);
                if($num>1) {
                    foreach($all as $na) {
                        if($na) {
                            $valid=true;
                            foreach($filter as $n=>$v) {
                                if(isset($na[$n]) && $na[$n]!=$v) {
                                    $valid = false;
                                    break;
                                }
                                unset($n, $v);
                            }
                            if($valid) break;
                        }
                        unset($na, $valid);
                    }
                }
                if(isset($valid) && $valid) $a = $na;
                else $a=$all[0];
                unset($all, $na, $valid);
            } else {
                $a = yaml_parse_file($s);
            }
        } else {
            $a = \Spyc::YAMLLoad($s);
        }
        if($cn && class_exists($cn)) {
            $a = new $cn($a);
        }
        Cache::set($ckey, $a, $timeout);
        unset($readTimeout, $ckey, $parser);
        return $a;
    }

    /**
     * Loads YAML string and converts to a PHP array
     *
     * @param string $s string to be parsed
     * 
     * @return array contents of the YAML text
     */
    public static function parse($s, $timeout=3600)
    {
        $ckey = 'yaml/'.md5($s);
        $a = Cache::get($ckey, $timeout);
        if ($a) {
            unset($ckey);
            return $a;
        }
        $parser = self::parser();
        if($parser=='php-yaml') {
            $a = yaml_parse($s);
        } else {
            $a = \Spyc::YAMLLoadString($s);
        }
        Cache::set($ckey, $a, $timeout);
        unset($timeout, $ckey, $parser);
        return $a;
    }


    /**
     * Loads YAML text and converts to a PHP array
     *
     * @param string $s file name or YAML string to load
     * 
     * @return array contents of the YAML text
     */
    public static function load($s, $timeout=3600)
    {
        if(is_array($s)) return false;
        if(strlen($s)<255 && file_exists($s)) {
            return self::read($s, $timeout);
        } else {
            return self::parse($s, $timeout);
        }
    }

    /**
     * Saves YAML file
     *
     * @param string $s file name
     * @param mixed $a arguments to be converted to YAML
     * 
     * @return array contents of the YAML text
     */
    public static function save($s, $a, $indent=2, $wordwrap=80, $mask=0666)
    {
        return bird::save($s, self::dump($a, $indent, $wordwrap), true, $mask);
    }

    /**
     * Dumps YAML content from params
     *
     * @param mixed $a arguments to be converted to YAML
     * 
     * @return string YAML formatted string
     */
    public static function dump($a, $indent=2, $wordwrap=80)
    {
        $parser = self::parser();
        if($parser=='php-yaml') {
            ini_set('yaml.output_indent', $indent);
            ini_set('yaml.output_width', $wordwrap);
            return yaml_emit($a);
        } else {
            return \Spyc::YAMLDump($a, $indent, $wordwrap);
        }
    }

    /**
     * Appends YAML text to memory object and yml file
     *
     * @param string $s file name or YAML string to load
     * 
     * @return array contents of the YAML text
     */
    public static function append($s, $arg, $timeout=1800)
    {
        $text = $arg;
        if(is_array($arg)) {
            $text = "\n".preg_replace('/^---[^\n]*\n?/', '', self::dump($arg));
        } else {
            $arg = self::parse($arg);
        }
        $yaml = self::load($s);
        $a = bird::mergeRecursive($yaml, $arg);
        if($a!=$yaml) {
            $ckey = 'yaml/'.md5($s);
            Cache::set($ckey, $a, $timeout);
            file_put_contents($s, $text, FILE_APPEND);
        }
        return $a;
    }

}