<?php
defined('PHPFOX') or exit('NO DICE!');

class User_Component_Controller_Admincp_Downloadtemplatefile extends Phpfox_Component
{
    public function process()
    {
        Phpfox::getUserParam('admincp.has_admin_access', true);
        $sFilePath = PHPFOX_DIR . 'module' . PHPFOX_DS . 'user' . PHPFOX_DS . 'static' . PHPFOX_DS . 'file' . PHPFOX_DS  . 'template' . (Phpfox::getParam('core.enable_register_with_phone_number') ? '_phone' : '') . '.csv';
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($sFilePath).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($sFilePath));
        readfile($sFilePath);
        exit;
    }
}