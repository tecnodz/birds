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
class Builder
{
    public static $prefix, $namespace, $lib, $category, $package, $author, $copyright, $license, $version, $link;

    public static function load($t, $f=null, $n=null)
    {
        if(is_null($n)) {
            $dbs = \bird::app()->Data;
            foreach($dbs as $n=>$a) {
                $cn = 'Birds\\Data\\'.ucfirst(substr($a['dsn'], 0, strpos($a['dsn'], ':'))).'Schema';
                if($s = $cn::load($n, $t, array('class'=>self::className($t), 'connection'=>$n))) {
                    break;
                }
                unset($cn, $o, $n, $s);
            }
        } else {
            $a = \bird::app()->Data[$n];
            $cn = 'Birds\\Data\\'.ucfirst(substr($a['dsn'], 0, strpos($a['dsn'], ':'))).'Schema';
            $s = $cn::load($n, $t, array('class'=>self::className($t), 'connection'=>$n));
        }
        if(!$s) {
            \bird::debug(__METHOD__, $cn, $t);
        }
        if($f===true) {
            $f = \bird::isWritable(\bird::app()->Birds['schema-dir'], $t.'.yml');
        }
        if($f && isset($s) && $s) {
            \Birds\Yaml::save($f, $s);
            self::className($t, $s, true);
        }
        return new \Birds\Schema($s);
    }

    public static function className($tn, $schema=array(), $save=false)
    {
        if(!$save && in_array($tn, \Birds\Schema::$schemas)) {
            return array_search($tn, \Birds\Schema::$schemas);
        }
        $cn = ((self::$namespace)?(self::$namespace.'\\'):(''))
            . ((self::$prefix)?(self::$prefix):(''))
            . \bird::camelize($tn, true);
        if(!class_exists($cn) && $save) {
            if(is_null(self::$lib)) {
                self::$lib = array(
                    BIRD_SITE_ROOT.'/lib',
                    BIRD_APP_ROOT.'/lib',
                );
            }
            $cols = (isset($schema['columns']))?(array_keys($schema['columns'])):(array());
            if(isset($schema['relations'])) $cols = array_merge($cols, array_keys($schema['relations']));

            if($f=\bird::isWritable(self::$lib, preg_replace('/[\\\_]+/', '/', $cn).'.php')) {
                $code = '<?'."php\n"
                    . "/**\n"
                    . " * {$cn}\n"
                    . " *\n"
                    . " * PHP version 5.3\n"
                    . " *\n"
                    . ((self::$category) ?(" * @category  ".self::$category."\n"):(''))
                    . ((self::$package)  ?(" * @package   ".self::$package."\n"):(''))
                    . ((self::$author)   ?(" * @author    ".self::$author."\n"):(''))
                    . ((self::$copyright)?(" * @copyright ".self::$copyright."\n"):(''))
                    . ((self::$license)  ?(" * @license   ".self::$license."\n"):(''))
                    . ((self::$version)  ?(" * @version   ".self::$version."\n"):(''))
                    . ((self::$link)     ?(" * @link      ".self::$link."\n"):(''))
                    . " */\n"
                    . ((self::$namespace)?("namespace ".self::$namespace.";\nclass ".substr($cn, strlen(self::$namespace)+1)):("class $cn"))
                    . " extends \\Birds\\Model\n{\n"
                    . "    public static \$schemaid='{$tn}';\n"
                    . ((count($cols)>0)?('    protected $'.implode(', $', $cols).";\n"):(''))
                    . "}\n";
                \bird::save($f, $code, true);
                unset($f, $code);
            } else {
                $cn = false;
            }
        }
        \Birds\Schema::$schemas[$cn]=$tn;
        return $cn;
    }
}

