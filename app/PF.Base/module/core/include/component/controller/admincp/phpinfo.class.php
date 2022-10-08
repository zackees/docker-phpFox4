<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Controller_Admincp_Phpinfo extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
		ob_clean();
		
		phpinfo();
		
		$sPhpInfo = ob_get_contents();

		ob_clean();

		$sPhpInfo = str_replace('<table>','<table class="table table-admin phpinfo-table">', $sPhpInfo);
        $sPhpInfo = str_replace('body {','.phpinfo-table body {', $sPhpInfo);
        $sPhpInfo = str_replace('pre {','.phpinfo-table pre {', $sPhpInfo);
        $sPhpInfo = str_replace('a:link {','.phpinfo-table a:link {', $sPhpInfo);
        $sPhpInfo = str_replace('a:hover {','.phpinfo-table a:hover {', $sPhpInfo);
        $sPhpInfo = str_replace('table {','table_remove {', $sPhpInfo);
        $sPhpInfo = str_replace('.center {','.phpinfo-table .center {', $sPhpInfo);
        $sPhpInfo = str_replace('.center table {','.phpinfo-table .center table {', $sPhpInfo);
        $sPhpInfo = str_replace('.center th {','.phpinfo-table .center th {', $sPhpInfo);
        $sPhpInfo = str_replace('td, th {','.phpinfo-table td, .phpinfo-table th {', $sPhpInfo);
        $sPhpInfo = str_replace('font-size: 75%;','font-size: 90%;', $sPhpInfo);
        $sPhpInfo = str_replace('h1 {','.phpinfo-table h1 {', $sPhpInfo);
        $sPhpInfo = str_replace('h2 {','.phpinfo-table h2 {', $sPhpInfo);
        $sPhpInfo = str_replace('.p {','.phpinfo-table .p {', $sPhpInfo);
        $sPhpInfo = str_replace('.e {','.phpinfo-table .e {', $sPhpInfo);
        $sPhpInfo = str_replace('.h {','.phpinfo-table .h {', $sPhpInfo);
        $sPhpInfo = str_replace('.v {','.phpinfo-table .v {', $sPhpInfo);
        $sPhpInfo = str_replace('.v i {','.phpinfo-table .v i  {', $sPhpInfo);
        $sPhpInfo = str_replace('img {','.phpinfo-table img {', $sPhpInfo);
        $sPhpInfo = str_replace('hr {','.phpinfo-table hr {', $sPhpInfo);

		$this->template()->setTitle(_p('php_info'))
			->setBreadCrumb(_p('system'), $this->url()->makeUrl('admincp.core.system'))
			->setSectionTitle(_p('php_info'))
			->assign(array(
				'sPhpInfo' => $sPhpInfo
			)
		);			
	}
	
	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('core.component_controller_phpinfo_clean')) ? eval($sPlugin) : false);
	}
}