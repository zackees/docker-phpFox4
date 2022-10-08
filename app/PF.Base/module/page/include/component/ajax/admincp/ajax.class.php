<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 *
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author           phpFox LLC
 * @package          Module_Page
 * @version          $Id: ajax.class.php 225 2009-02-13 13:24:59Z phpFox LLC $
 */
class Page_Component_Ajax_Admincp_Ajax extends Phpfox_Ajax
{
    public function addUrl()
    {
        $this->call("if ($('#title_url').val() == '') { $('#js_url_table').show(); $('#title_url').val('" . Phpfox::getService('page')->prepareTitle($this->get('title')) . "'); }");
    }

    public function checkUrl()
    {
        $this->call('$(\'#js_url_table\').find(\'.js_warning\').remove();');
        $titleUrl = $this->get('title_url');
        $oldUrl = $this->get('old_url');

        if ($titleUrl != $oldUrl) {
            list($preparedUrl, $existed) = Phpfox::getService('page')->prepareTitle($titleUrl, true);
            if($existed) {
                $sText = _p('the_url_title_original_url_is_already_existed_instead_please_use_our_recommended_url_listed_above', ['original_url' => $titleUrl]);
            }
            else if($titleUrl != $preparedUrl) {
                $sText = _p('the_url_title_original_url_is_not_supported_we_do_not_allow_caps_or_spaces_instead_please_use_our_recommended_url_listed_above', ['original_url' => $titleUrl]);
            }
            $this->call('$(\'#js_url_table\').append(\'<div class="text-danger js_warning">' . $sText . '</div>\').find(\'#title_url\').val(\'' . $preparedUrl . '\');');
        }
    }
}