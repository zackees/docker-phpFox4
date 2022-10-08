<?php
/**
 * [PHPFOX_HEADER]
 * 
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author			phpFox LLC
 * @package 		Phpfox
 * @version 		$Id: validator.sett.php 5840 2013-05-09 06:14:35Z phpFox LLC $
 */

defined('PHPFOX') or exit('NO DICE!');

$this->_aDefaults = array(
	'username' => array(
		'pattern' => str_replace(['min', 'max'],[Phpfox::getParam('user.min_length_for_username'), Phpfox::getParam('user.max_length_for_username')],Phpfox::getParam('core.username_regex_rule')),
		'title' => (defined('PHPFOX_INSTALLER') ? '' : _p('provide_a_valid_user_name', array('min' => Phpfox::getParam('user.min_length_for_username'), 'max' => Phpfox::getParam('user.max_length_for_username'))))
	),
	'full_name' => array(
		'pattern' => str_replace('max', Phpfox::getParam('user.maximum_length_for_full_name'), (Phpfox::getParam('core.fullname_regex_rule'))),
		'title' => (defined('PHPFOX_INSTALLER') ? '' :
			(Phpfox::getParam('user.display_or_full_name') == 'full_name' ?
                    _p('provide_a_valid_full_name', ['max' => Phpfox::getParam('user.maximum_length_for_full_name')]) :
                     _p('provide_a_valid_display_name', ['max' => Phpfox::getParam('user.maximum_length_for_full_name')]))
		)
	),
    'email' => array(
        'pattern' => '/^[0-9a-zA-Z]([\-+.\w]*[0-9a-zA-Z]?)*@([0-9a-zA-Z\-.\w]*[0-9a-zA-Z]\.)+[a-zA-Z]{2,}$/',
        'maxlen' => 100,
        'title' => (defined('PHPFOX_INSTALLER') ? '' : _p('provide_a_valid_email_address'))
	),
    'password' => array(
    	'minlen' => Phpfox::getParam('user.min_length_for_password'),
    	'maxlen' => Phpfox::getParam('user.max_length_for_password'),
    	'title' => (defined('PHPFOX_INSTALLER') ? '' : _p('not_a_valid_password'))
    ),
    'url' => array(
       'pattern' => Phpfox::getParam('core.url_regex_rule'),
       'maxlen'=> 255,
       'minlen'=> 11,
       'title' => (defined('PHPFOX_INSTALLER') ? '' : _p('invalid_url'))
    ),
	'int' => array(
       'pattern' => '/^[0-9]$/',
       'title' => (defined('PHPFOX_INSTALLER') ? '' : _p('provide_a_numerical_value'))
    ),
    'money' => array(
        'pattern'=>'/[0-9.,]$/',
        'title' => (defined('PHPFOX_INSTALLER') ? '' : _p('provide_a_valid_price'))
    ),
    'year' => array(
    	'pattern' => '/^[0-9]{4}$/',
    	'title' => (defined('PHPFOX_INSTALLER') ? '' : _p('provide_a_valid_year_eg_1982'))
    ),
    'zip'  => array(
        'pattern'=>'/^[a-zA-Z\d\-\s]{0,20}$/'
    ),
	'special_characters' => array(
		'pattern'=> Phpfox::getParam('core.special_characters_regex_rule')
	),
	'html' => array(
		'pattern'=> Phpfox::getParam('core.html_regex_rule')
	),
	'currency_id' => array(
		'pattern'=> Phpfox::getParam('core.currency_id_regex_rule')
	)
);

?>
