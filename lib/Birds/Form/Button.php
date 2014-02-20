<?php
/**
 * Form
 *
 * PHP version 5.3
 *
 * @category  Form
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2014 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @version   SVN: $Id$
 * @link      http://tecnodz.com/
 */

/**
 * Model
 *
 * @category  Form
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2014 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @link      http://tecnodz.com/
 */
namespace Birds\Form;
class Button extends \Birds\Form
{
    const ATTRIBUTES=' id class name type value ';
    public $node='button', $type='button';

    public function render()
    {
        $b=$a='';
        $c = \bird::$cssPrefix.'button'
            . ((isset($this->class))?(' button-'.str_replace(' button-', ' ', $this->class)):(''));
        if(isset($this->block)) {
            $e = (is_string($this->block))?($this->block):('div');
            $b = '<'.$e
                . ((isset($this->id))?(' id="i__'.$this->id.'"'):(''))
                . ' class="'.$c.'">'.$b;
            $a = '</'.$e.'>';
        }
        if(isset($this->label)) {
            if(!$this->attributes) $this->attributes=array();
            $this->attributes['class'] = $c;
        }
        unset($c);

        return $b.parent::render().$a;
    }
}