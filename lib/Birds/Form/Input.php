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
class Input extends \Birds\Form
{
    const ATTRIBUTES=' id class name type value ';
    public $node='input', $type='text';


    public function render()
    {
        $b=$a='';
        if(isset($this->block)) {
            $e = (is_string($this->block))?($this->block):('div');
            $b = '<'.$e
                . ((isset($this->id))?(' id="i__'.$this->id.'"'):(''))
                . ' class="'
                    . \bird::$cssPrefix.'input'
                    . ((isset($this->class))?(' input-'.str_replace(' input-', ' ', $this->class)):(''))
                .'">'.$b;
            $a = '</'.$e.'>';
        }
        if(isset($this->label)) {
            $b .= '<label><span class="'.\bird::$cssPrefix.'label">'.\bird::xml($this->label).'</span>';
            $a = '</label>'.$a;
        }

        return $b.parent::render().$a;
    }
}