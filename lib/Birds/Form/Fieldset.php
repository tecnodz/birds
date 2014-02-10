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
class Fieldset extends \Birds\Form
{
    const ATTRIBUTES=' id class ';
    public $node='fieldset', $type='fieldset';

    public function renderContent()
    {
        return  ((isset($this->label))?('<legend>'.\bird::xml($this->label).'</legend>'):(''))
        	  . parent::renderContent();
    }
}