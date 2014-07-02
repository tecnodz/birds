<?php
/**
 * E-studio Content Managemt System: pages
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
class Content extends \Birds\Model
{
    public static $schemaid='estudio_content';
    protected $page, $id, $slot, $priority, $class, $method, $params, $content, $prepare, $modified, $published, $EstudioPage;

    public function getUid()
    {
        return $this->page.':'.$this->id;
    }

    public function getPrepare()
    {
        return (bool) $this->prepare;
    }
}