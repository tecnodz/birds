<?php
/**
 * E-studio Content Managemt System: page
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
class Page extends \Birds\Model
{
    public static $schemaid='estudio_page';
    protected $id, $url, $language, $title, $formats, $script, $stylesheet, $multiview, $created, $modified, $published, $EstudioContent;


    public static function match($url, $exception=true)
    {
        $cn = get_called_class();
        $f=array('where'=>array('url'=>$url,'published<'=>date('Y-m-d\TH:i:s')));
        $P = $cn::find($f);
        if($i=$P->count()) {
            return $P->fetch(0);
        } else if($exception) {
            throw new \Birds\Http\Exception(404);
        } else {
            return false;
        }
    }

    public function render($format='text/html')
    {
        \bird::debug(__METHOD__, var_export($this, true), false);
        \bird::debug(__METHOD__, var_export($this->relation('EstudioContent'), true));
    }

}