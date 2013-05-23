<?php
/**
 * Birds unit test for Assets::minify
 */
class MinifyTest extends Birds\Test 
{
	public static $name = 'Birds\\App\\Assets::minify';

	public static function test()
	{
		$s = '<script type="text/javascript" src="/test.js"></script><link rel="stylesheet" type="text/css" href="/_/css/birds.less" />';
		Birds\bird::debug('', Birds\App\Assets::minify($s));
	}
}



