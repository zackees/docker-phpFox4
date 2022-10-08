<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Controller_Index_Visitor extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        if ($sPlugin = Phpfox_Plugin::get('core.component_controller_index_visitor_start')) {
            eval($sPlugin);
        }

        $image = [];
        if (Phpfox::isAppActive('Core_Photos')) {
            list(, $featured) = Phpfox::getService('photo')->getFeatured();
        }
        else {
            $featured = [];
        }

        if (is_array($featured) && isset($featured[0])) {
            $photo = $featured[0];
            $url = Phpfox_Image_Helper::instance()->display([
                'server_id' => $photo['server_id'],
                'path' => 'photo.url_photo',
                'file' => $photo['destination'],
                'suffix' => '_1024',
                'return_url' => true
            ]);
            $image = [
                'image' => $url,
                'info' => strip_tags($photo['title']) . ' by ' . $photo['full_name']
            ];
        }

		$oAssets = Phpfox::getLib('assets');
        if (!$image) {
            $images = [
                'create-a-community-for-musicians.jpg' => _p('creating_communities_for_musicians'),
                'create-a-community-for-athletes.jpg' => _p('creating_communities_for_athletes'),
                'create-a-community-for-photographers.jpg' => _p('creating_communities_for_photographers'),
                'create-a-social-network-for-fine-cooking.jpg' => _p('creating_communities_for_fine_cooking')
            ];
            $total = rand(1, (count($images)));
            $image = [];
            $cnt = 0;

            foreach ($images as $image => $info) {
                $cnt++;
                $image = [
                    'image' => $oAssets->getAssetUrl('PF.Base/module/core/static/image/' . $image),
                    'info' => $info
                ];
                if ($cnt === $total) {
                    break;
                }
            }
        }

        $content = '';
        if ($sPlugin = Phpfox_Plugin::get('core.component_controller_index_visitor_end')) {
            eval($sPlugin);
        }

        // Get Image
        if ($image) {
            $this->template()->setMeta('og:image', $image['image']);
        }
        else {
            $this->template()->setMeta('og:image', '');
        }

        $this->template()->setHeader('cache', array(
                'country.js' => 'module_core',
            )
        )
            ->setBreadCrumb(Phpfox::getParam('core.site_title'))
            ->setPhrase(array(
                    'continue'
                )
            )->assign(array(
                    'aSettings' => Phpfox::getService('custom')->getForEdit(array(
                        'user_main',
                        'user_panel',
                        'profile_panel'
                    ), null, null, true),
                    'image' => $image,
                    'content' => $content,
                )
            )->setHeader('cache', [
                'jquery/plugin/intlTelInput.js' => 'static_script',
            ]);
    }
}
