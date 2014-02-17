<?php
/**
 * Data validation according to schema definition
 *
 * PHP version 5.3
 *
 * @category  Validator
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2014 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @version   SVN: $Id$
 * @link      http://tecnodz.com/
 */
namespace Birds;
class Validator
{
    const CATEGORY      = 'Validator';
    const ERROR_VALUE   = 'This is not a valid value.';
    const ERROR_INT     = 'An integer number is expected.';
    const ERROR_MIN     = 'The minimum allowed value is %s';
    const ERROR_MAX     = 'The maximum allowed value is %s';
    const ERROR_NULL    = 'It\'s mandatory and should not be blank.';

    public static self::$error;

    public static function check($def, $value, $exception=true)
    {
        $v0 = $value;
        self::$error=array();
        if ($def['type']=='string') {
            $value = (is_array($value))?(implode('', $value)):((string) $value);
            if (isset($def['size']) && $def['size'] && strlen($value) > $def['size']) {
                $value = substr($value, 0, (int)$def['size']);
            }
        } else if($def['type']=='int') {
            if (!is_numeric($value) && $value!='') {
                self::$error[] = bird::t(self::ERROR_VALUE, self::CATEGORY);
                self::$error[] = bird::t(self::ERROR_INT, self::CATEGORY);
            } else {
                if($value!=='' && !is_null($value)) $value = (int) $value;
                if (isset($def['min']) && $value < $def['min']) {
                    self::$error[] = bird::t(self::ERROR_MIN, self::CATEGORY, $def['min']);
                }
                if (isset($def['max']) && $value > $def['max']) {
                    self::$error[] = bird::t(self::ERROR_MAX, self::CATEGORY, $def['max']);
                }
            }
        } else if(substr($def['type'], 0,4)=='date') {
            if($value) {
                $time = false;
                $d = false;
                if(!preg_match('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}/', $value)) {
                    $format = bird::$dateFormat;
                    if (substr($def['type'], 0, 8)=='datetime') {
                        $format .= ' '.bird::$timeFormat;
                        $time = true;
                    }
                    $d = date_parse_from_format($format, $value);
                }
                if($d) {
                    $value = str_pad((int)$d['year'], 4, '0', STR_PAD_LEFT)
                        . '-' . str_pad((int)$d['month'], 2, '0', STR_PAD_LEFT)
                        . '-' . str_pad((int)$d['day'], 2, '0', STR_PAD_LEFT);
                    if($time) {
                        $value .= ' '.str_pad((int)$d['hour'], 2, '0', STR_PAD_LEFT)
                            . ':' . str_pad((int)$d['minute'], 2, '0', STR_PAD_LEFT)
                            . ':' . str_pad((int)$d['second'], 2, '0', STR_PAD_LEFT);
                    }
                } else if($d = strtotime($value)) {
                    //$nvalue = (substr($def['type'], 0, 8)=='datetime')?(date('Y-m-d H:i:s', $d)):(date('Y-m-d', $d));
                }
            }
        }
        if ($value=='' && isset($def['null']) && !$def['null']) {
            self::$error[] = bird::t(self:::ERROR_NULL, self::CATEGORY);
        } else if(isset($def['choices'])) {
            // validate choice
        }
        if(isset(self::$error[0])) {
            if($exception) {
                throw new \Exception(implode(' ', self::$error));
            } else {
                return false;
            }
        }
        return $value;
    }
}