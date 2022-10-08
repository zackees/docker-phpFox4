<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

use MatthiasMullie\Minify;

class Phpfox_File_Minimize {
	/**
	 * @var Minify\CSS
	 */
	private $_minify;

	/**
	 * @return $this
	 */
	public static function instance() {
		return Phpfox::getLib('file.minimize');
	}

	public function css($path, $files) {
		$content = '';
		$oAssets = Phpfox::getLib('assets');
		foreach ($files as $file) {
			$sContent = file_get_contents($file);
			$sContent = preg_replace_callback('/url\([\'"](.*?)[\'"]\)/is',  function($aMatches) use($file, $oAssets) {
				$sMatch = trim($aMatches[1]);
				$sMatch = str_replace('../../../../', '', $sMatch);
				$sMatch = trim($sMatch);

				$path = $sMatch;

				if(preg_match("#^(PF\.Base|PF\.Site)#", $sMatch)){
					$path = $oAssets->getAssetUrl($sMatch, false);
				}else if(substr($sMatch, 0, 3) === '../'){
					$path = dirname(dirname($file)).'/'.substr($sMatch, 3);
					$path = $oAssets->getAssetUrl($path, false);
				}else if(substr($sMatch, 0, 2) === './'){
					$path = dirname($file).'/'.substr($sMatch, 2);
					$path = $oAssets->getAssetUrl($path, false);
				}

				if(substr($path, 0,7) == 'http://'){
				    $path  = substr($path, 5);
                }elseif(substr($path, 0,8) == 'https://'){
                    $path  = substr($path, 6);
                }

				return 'url(\'' . $path . '\')';
			}, $sContent);
			$sContent = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $sContent);
			$sContent = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $sContent);

			$content .= $sContent;
		}

		file_put_contents($path, $content);

		return true;
	}

	public function _replaceImages($aMatches) {
		$sMatch = trim($aMatches[1]);
		$sMatch = str_replace('../', '', $sMatch);

		d($aMatches);

		return 'url(\'' . Phpfox_Template::instance()->getStyle('image', $sMatch) . '\')';
	}

	public function js($path, $content, $extra = '') {
		$this->_minify = new Minify\JS();

		if (!is_array($content)) {
			$content = [$content];
		}

		foreach ($content as $file) {
			$this->_minify->add($file);
		}

		$data = $this->_minify->minify();

		file_put_contents($path, $data);

        if ($extra)
        {
            file_put_contents($path, $extra, FILE_APPEND);
        }

		return true;
	}
}