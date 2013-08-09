<?php
/**
 * OAuth authentication
 *
 * This package enables various OAuth SSO
 *
 * PHP version 5.3
 *
 * @category  Oauth
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @version   SVN: $Id$
 * @link      http://tecnodz.com/
 */

/**
 * OAuth authentication
 *
 * This package enables various OAuth SSO
 *
 * @category  Oauth
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @link      http://tecnodz.com/
 */
namespace Birds\Oauth;
class Facebook extends \Birds\Oauth {
    
    protected 
        $prefix = 'facebook',
        $authUrl = 'https://www.facebook.com/dialog/oauth',
        $tokenUrl = 'https://graph.facebook.com/oauth/access_token',
        $graphUrl = 'https://graph.facebook.com/me',
        $scope = array('email');
    
    public static
        $sessionMap=array('id'=>'id', 'name'=>'name', 'username'=>'username', 'image'=>'http://graph.facebook.com/$id/picture?type=large', 'email'=>'email', 'modified'=>'updated_time');
}