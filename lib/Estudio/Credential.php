<?php
/**
 * E-studio Content Managemt System: credentials
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
class Credential extends \Birds\Model
{
    public static $schemaid='estudio_credential';
    protected $assign, $role, $id, $require, $certificate, $http, $group, $user, $ip, $modified;
}