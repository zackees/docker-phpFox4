<?php
/**
 *
 * @author  phpFox LLC
 * @license phpfox.com
 */

class Admincp_Service_Sidebar
{

    /**
     * @var array
     */
    protected $aMenus = [];

    /**
     * @var string
     */
    protected $activeTags = null;

    /**
     * @var string
     */
    protected $defaultTags = 'admincp.apps';

    /**
     * Admincp_Service_Sidebar constructor.
     */
    public function __construct()
    {
        $this->initDashboardMenus();
        $this->initAppsMenus();
        $this->initGlobalizeMenus();
        $this->initAppearanceMenus();
        $this->initMemberMenus();
        $this->initSettingsMenus();
        $this->initMaintenanceMenus();
        $this->initTechieMenus();
        $this->initLogoutMenus();

        (($sPlugin = Phpfox_Plugin::get('admincp_get_main_menus')) ? eval($sPlugin) : false);

    }

    /**
     * @param string $tags
     *
     * @return $this
     */
    public function setActive($tags)
    {
        $this->activeTags = $tags;
        return $this;
    }

    /**
     *
     */
    public function initDashboardMenus()
    {
        $this->aMenus['dashboard'] = [
            'icon'  => 'ico ico-bar-chart-2',
            'label' => _p('dashboard'),
            'link'  => Phpfox::getLib('url')->makeUrl('admincp'),
            'tags'  => 'admincp.dashboard',
        ];
    }

    /**
     *
     */
    public function initAppsMenus()
    {
        $this->aMenus['apps'] = [
            'icon'  => 'ico ico-box',
            'label' => _p('apps'),
            'link'  => Phpfox::getLib('url')->makeUrl('admincp.apps'),
            'tags'  => 'admincp.apps',
            'items' => [
                'installed'        => [
                    'icon'  => '',
                    'label' => _p('installed'),
                    'link'  => Phpfox_Url::instance()->makeUrl('admincp.apps'),
                    'tags'  => 'admincp.apps',
                ],
				'uploaded'        => [
					'icon'  => '',
					'label' => _p('Uploaded'),
					'link'  => Phpfox_Url::instance()->makeUrl('admincp.apps.uploaded'),
					'tags'  => 'admincp.apps.uploaded',
				],
                'purchase_history' => [
                    'icon'  => '',
                    'label' => _p('purchase_history'),
                    'link'  => Phpfox_Url::instance()->makeUrl('admincp.store.orders'),
                    'tags'  => 'admincp.store.orders',
                ],
                'find_more'        => [
                    'icon'  => '',
                    'label' => _p('find_more'),
                    'link'  => Phpfox_Url::instance()->makeUrl('admincp.store', ['load' => 'apps']),
                    'tags'  => 'admincp.store.find_more',
                ]
            ]
        ];
    }

    /**
     *
     */
    public function initSettingsMenus()
    {
        $oUrl = Phpfox::getLib('url');

        list($aGroups,) = Phpfox::getService('admincp.setting.group')->get();

        $aCache = $aGroups;
        $aGroups = [];

        foreach ($aCache as $key => $value) {
            $n = $key;
            switch ($value['group_id']) {
                case 'cookie':
                    $n = _p('browser_cookies');
                    break;
                case 'site_offline_online':
                    $n = _p('toggle_site');
                    break;
                case 'general':
                    $n = _p('site_settings');
                    break;
                case 'mail':
                    $n = _p('mail_server');
                    break;
                case 'spam':
                    $n = _p('spam_assistance');
                    break;
                case 'email':
                    $n = _p('emails');
                    break;
                case 'regex':
                    $n = _p('regex_rules');
                    break;
                case 'seo':
                    $n = _p('setting_group_label_seo');
                    break;
                case 'ssl':
                    $n = _p('setting_group_label_ssl');
                    break;
                case 'server_settings':
                    $n = _p('setting_group_label_server_settings');
                    break;
                case 'time_stamps':
                    $n = _p('timestamp');
                    break;
                case 'registration':
                    continue 2;
            }
            $aGroups[$n] = $value;
        }
        ksort($aGroups);

        $aSettings = [];
        foreach ($aGroups as $sGroupName => $aGroupValues) {
            if (in_array($aGroupValues['group_id'], ['cookie', 'security', 'site_offline_online'])) {
                continue;
            }
            $aSettings[$sGroupName] = [
                'icon'  => '',
                'label' => $sGroupName,
                'link'  => $oUrl->makeUrl('admincp.setting.edit', ['group-id' => $aGroupValues['group_id']]),
                'tags'  => 'admincp.settings.' . $aGroupValues['group_id'],
            ];
        }
        $aSettings = array_merge($aSettings, [
            'app_settings'=> [
                'icon'  => '',
                'label' => _p('app_settings'),
                'link'  => $oUrl->makeUrl('admincp.setting.manage'),
                'tags'  => 'admincp.setting.manage',
            ],
        	'storage_system'=> [
				'icon'  => '',
				'label' => _p('storage_system'),
				'link'  => $oUrl->makeUrl('admincp.setting.storage.manage'),
				'tags'  => 'admincp.setting.storage',
			],
			'assets_handling'=> [
				'icon'  => '',
				'label' => _p('assets_handling'),
				'link'  => $oUrl->makeUrl('admincp.setting.assets.manage'),
				'tags'  => 'admincp.setting.assets',
			],
			'log_handling'=> [
				'icon'  => '',
				'label' => _p('log_handling'),
				'link'  => $oUrl->makeUrl('admincp.setting.logger.manage'),
				'tags'  => 'admincp.setting.logger',
			],
			'session_handling'=> [
				'icon'  => '',
				'label' => _p('session_handling'),
				'link'  => $oUrl->makeUrl('admincp.setting.session.manage'),
				'tags'  => 'admincp.setting.session',
			],
			'message_queue'=> [
				'icon'  => '',
				'label' => _p('message_queue'),
				'link'  => $oUrl->makeUrl('admincp.setting.queue.manage'),
				'tags'  => 'admincp.setting.queue',
			],
            'time_zones'           => [
                'icon'  => '',
                'label' => _p('time_zones'),
                'link'  => $oUrl->makeUrl('admincp.setting.timezone'),
                'tags'  => 'admincp.setting.time_zones',
            ],
            'short_urls'           => [
                'icon'  => '',
                'label' => _p('short_urls'),
                'link'  => $oUrl->makeUrl('admincp.setting.url'),
                'tags'  => 'admincp.setting.short_urls',
            ],
            'url_match'            => [
                'icon'  => '',
                'label' => _p('URL Match'),
                'link'  => $oUrl->makeUrl('admincp.setting.redirection'),
                'tags'  => 'admincp.setting.redirection',
            ],
            'registration'         => [
                'icon'  => '',
                'label' => _p('registration_settings'),
                'link'  => $oUrl->makeUrl('admincp.setting.edit', ['group-id' => 'registration']),
                'tags'  => 'admincp.settings.registration',
            ],
            'notification'         => [
                'icon'  => '',
                'label' => _p('notification_settings'),
                'link'  => $oUrl->makeUrl('admincp.setting.notification', ['group-id' => 'notification']),
                'tags'  => 'admincp.setting.notification',
            ],
            'data_cache'           => [
                'icon'  => '',
                'label' => _p('Data Cache'),
                'link'  => $oUrl->makeUrl('/admincp/app/settings', ['id' => 'PHPfox_Core', 'group' => 'core_cache_driver']),
                'tags'  => 'admincp.settings.core_cache_driver',
            ],
            'cron_job'             => [
                'icon'  => '',
                'label' => _p('Cron Job'),
                'link'  => $oUrl->makeUrl('/admincp/app/settings', ['id' => 'PHPfox_Core', 'group' => 'cron_job']),
                'tags'  => 'admincp.settings.cron_job',
            ],
            'anti_spam_questions'  => [
                'icon'  => '',
                'label' => _p('anti_spam_questions'),
                'link'  => $oUrl->makeUrl('admincp.user.spam'),
                'tags'  => 'admincp.user.spam',
            ],
            'cancellation_options' => [
                'icon'  => '',
                'label' => _p('cancellation_options'),
                'link'  => $oUrl->makeUrl('admincp.user.cancellations.manage'),
                'tags'  => 'admincp.user.cancellations',
            ],
            'license_key'          => [
                'icon'  => '',
                'label' => _p('license_key'),
                'link'  => $oUrl->makeUrl('admincp.setting.license'),
                'tags'  => 'admincp.settings.license',
            ],
        ]);

        $this->aMenus['settings'] = [
            'icon'  => 'ico ico-gear',
            'label' => _p('settings'),
            'link'  => '#',
            'items' => $aSettings,
        ];
    }

    /**
     *
     */
    public function initAppearanceMenus()
    {
        $oUrl = Phpfox::getLib('url');

        $this->aMenus['appearance'] = [
            'icon'  => 'ico ico-pen',
            'label' => _p('appearance'),
            'link'  => '#',
            'items' => [
                'theme' => [
                    'label' => _p('themes'),
                    'link'  => $oUrl->makeUrl('admincp.theme'),
                    'tags'  => 'admincp.appearance.theme',
                ],

                'page'  => [
                    'label' => _p('pages'),
                    'link'  => $oUrl->makeUrl('admincp.page'),
                    'tags'  => 'admincp.appearance.page',
                ],
                'menu'  => [
                    'label' => _p('menus'),
                    'link'  => $oUrl->makeUrl('admincp.menu'),
                    'tags'  => 'admincp.appearance.menu',
                ],
                'block' => [
                    'label' => _p('blocks'),
                    'link'  => $oUrl->makeUrl('admincp.block'),
                    'tags'  => 'admincp.appearance.block',
                ],
            ],
        ];

    }

    /**
     *
     */
    public function initMaintenanceMenus()
    {
        $oUrl = Phpfox::getLib('url');

        $this->aMenus['maintenance'] = [
            'icon'  => 'ico ico-power',
            'label' => _p('maintenance'),
            'link'  => '#',
            'items' => [
                'menu_cache_manager'           => [
                    'icon'  => '',
                    'label' => _p('menu_cache_manager'),
                    'link'  => $oUrl->makeUrl('admincp.maintain.cache'),
                    'tags'  => 'admincp.maintain.cache',
                ],
                'site_statistics'              => [
                    'icon'  => '',
                    'label' => _p('site_statistics'),
                    'link'  => $oUrl->makeUrl('admincp.core.stat'),
                    'tags'  => 'admincp.maintain.stat',
                ],
                'admincp_menu_system_overview' => [
                    'icon'  => '',
                    'label' => _p('admincp_menu_system_overview'),
                    'link'  => $oUrl->makeUrl('admincp.core.system'),
                    'tags'  => 'admincp.maintain.system',
                ],
                'admincp_menu_cron_manager' => [
                    'icon'  => '',
                    'label' => _p('menu_cron_manager'),
                    'link'  => $oUrl->makeUrl('admincp.cron.manager'),
                    'tags'  => 'admincp.maintain.cron',
                ],
                'reported_items'               => [
                    'icon'  => '',
                    'label' => _p('reported_items'),
                    'link'  => $oUrl->makeUrl('admincp.report'),
                    'tags'  => 'admincp.maintain.report',
                ],
                'search_words'               => [
                    'icon'  => '',
                    'label' => _p('search_words'),
                    'link'  => $oUrl->makeUrl('admincp.search.searchwords'),
                    'tags'  => 'admincp.maintain.searchwords',
                ],
                'admincp_menu_reparser'        => [
                    'icon'  => '',
                    'label' => _p('admincp_menu_reparser'),
                    'link'  => $oUrl->makeUrl('admincp.maintain.reparser'),
                    'tags'  => 'admincp.maintain.reparser',
                ],
                'remove_duplicates'            => [
                    'icon'  => '',
                    'label' => _p('remove_duplicates'),
                    'link'  => $oUrl->makeUrl('admincp.maintain.duplicate'),
                    'tags'  => 'admincp.maintain.duplicate',
                ],
                'Remove files no longer used'  => [
                    'icon'  => '',
                    'label' => _p('Remove files no longer used'),
                    'link'  => $oUrl->makeUrl('admincp.maintain.removefile'),
                    'tags'  => 'admincp.maintain.removefile',
                ],
                'counters'                     => [
                    'icon'  => '',
                    'label' => _p('counters'),
                    'link'  => $oUrl->makeUrl('admincp.maintain.counter'),
                    'tags'  => 'admincp.maintain.counter',
                ],
                'find_missing_settings'        => [
                    'icon'  => '',
                    'label' => _p('find_missing_settings'),
                    'link'  => $oUrl->makeUrl('admincp.setting.missing'),
                    'tags'  => 'admincp.maintain.missing',
                ],
                'rebuild_core_theme'           => [
                    'icon'  => '',
                    'label' => _p('Rebuild Core Theme'),
                    'link'  => $oUrl->makeUrl('admincp.theme.bootstrap.rebuild'),
                    'tags'  => 'admincp.maintain.rebuild_theme',
                    'event' => 'onClick="return flavor_rebuildTheme();"'
                ],
                'revert_core_theme'            => [
                    'icon'  => '',
                    'class' => 'sJsConfirm',
                    'label' => _p('Revert Bootstrap Theme'),
                    'link'  => $oUrl->makeUrl('flavors.manage', ['id' => 'bootstrap', 'type' => 'revert', 'process' => 'yes']),
                    'tags'  => 'admincp.maintain.revert_theme',
                ],
                'ban_filters'                  => [
                    'icon'  => '',
                    'label' => _p('ban_filters'),
                    'link'  => $oUrl->makeUrl('admincp.ban.email'),
                    'tags'  => 'admincp.maintain.ban',
                ],
                'toggle_site'                  => [
                    'icon'  => '',
                    'label' => _p('toggle_site'),
                    'link'  => $oUrl->makeUrl('admincp.setting.edit', ['group-id' => 'site_offline_online']),
                    'tags'  => 'admincp.settings.site_offline_online',
                ]
            ],
        ];
    }

    /**
     * @return bool
     */
    public function initTechieMenus()
    {

        if (!Phpfox::isTechie() or PHPFOX_LICENSE_ID != 'techie') {
            return false;
        }

        $oUrl = Phpfox::getLib('url');

        $this->aMenus['techie'] = [
            'icon'  => 'ico ico-mouse',
            'label' => _p('techie'),
            'link'  => '#',
            'items' => [
                'techie_product'   => [
                    'icon'  => '',
                    'label' => _p('products'),
                    'link'  => $oUrl->makeUrl('admincp.product'),
                    'tags'  => 'admincp.techie.product',
                ],
                'techie_plugins'   => [
                    'icon'  => '',
                    'label' => _p('plugins'),
                    'link'  => $oUrl->makeUrl('admincp.plugin'),
                    'tags'  => 'admincp.techie.plugin',
                ],
                'techie_component' => [
                    'icon'  => '',
                    'label' => _p('components'),
                    'link'  => $oUrl->makeUrl('admincp.component'),
                    'tags'  => 'admincp.techie.component',
                ],
            ],
        ];
    }

    /**
     *
     */
    public function initGlobalizeMenus()
    {
        $oUrl = Phpfox::getLib('url');

        $this->aMenus['globalize'] = [
            'icon'  => 'ico ico-globe',
            'label' => _p('globalization'),
            'link'  => '#',
            'items' => [
                'languages'             => [
                    'icon'  => '',
                    'label' => _p('languages'),
                    'link'  => $oUrl->makeUrl('admincp.language'),
                    'tags'  => 'admincp.globalize.language',
                ],
                'phrases'               => [
                    'icon'  => '',
                    'label' => _p('phrases'),
                    'link'  => $oUrl->makeUrl('admincp.language.phrase'),
                    'tags'  => 'admincp.globalize.phrase',
                ],
                'countries'             => [
                    'icon'  => '',
                    'label' => _p('countries'),
                    'link'  => $oUrl->makeUrl('admincp.core.country'),
                    'tags'  => 'admincp.globalize.country',
                ]
                ,
                'currencies'            => [
                    'icon'  => '',
                    'label' => _p('currencies'),
                    'link'  => $oUrl->makeUrl('admincp.core.currency'),
                    'tags'  => 'admincp.globalize.currency',
                ],
                'payment_gateways_menu' => [
                    'icon'  => '',
                    'label' => _p('payment_gateways_menu'),
                    'link'  => $oUrl->makeUrl('admincp.api.gateway'),
                    'tags'  => 'admincp.settings.payments',
                ],
            ],
        ];
    }

    /**
     *
     */
    public function initMemberMenus()
    {
        $oUrl = Phpfox::getLib('url');

        $items = array_merge([
            'search'               => [
                'icon'  => '',
                'label' => _p('browse_members'),
                'link'  => $oUrl->makeUrl('admincp.user.browse'),
                'tags'  => 'admincp.member.browse',
            ],
            'group'                => [
                'icon'  => '',
                'label' => _p('manage_user_groups'),
                'link'  => $oUrl->makeUrl('admincp.user.group'),
                'tags'  => 'admincp.member.group',
            ],
            'group_settings'       => [
                'icon'  => '',
                'label' => _p('user_group_settings'),
                'link'  => $oUrl->makeUrl('admincp.user.group.add', ['group_id' => 2, 'setting' => true, 'module' => 'core']),
                'tags'  => 'admincp.member.group_settings',
            ],
        ], Phpfox::isModule('subscribe') ? [
            'subscriptions'        => [
                'icon'  => '',
                'label' => _p('subscriptions'),
                'link'  => $oUrl->makeUrl('admincp.app', ['id' => 'Core_Subscriptions']),
                'tags'  => 'admincp.member.subscribe',
            ],
        ] : [], [
            'promotions'           => [
                'icon'  => '',
                'label' => _p('promotions'),
                'link'  => $oUrl->makeUrl('admincp.user.promotion'),
                'tags'  => 'admincp.member.promotion',
            ],
            'custom'               => [
                'icon'  => '',
                'label' => _p('custom_fields'),
                'link'  => $oUrl->makeUrl('admincp.custom'),
                'tags'  => 'admincp.member.custom',
            ],
            'settings'             => [
                'icon'  => '',
                'label' => _p('manage_settings'),
                'link'  => $oUrl->makeUrl('admincp.setting.edit', ['module-id' => 'user']),
                'tags'  => 'admincp.member.settings',
            ],
            'relationship_statues' => [
                'icon'  => '',
                'label' => _p('relationship_statues'),
                'link'  => $oUrl->makeUrl('admincp.custom.relationships'),
                'tags'  => 'admincp.member.relationships',
            ],
            'inactive_members'     => [
                'icon'  => '',
                'label' => _p('inactive_members'),
                'link'  => $oUrl->makeUrl('admincp.user.inactivereminder'),
                'tags'  => 'admincp.member.inactivereminder',
            ],
            'cancelled_members'    => [
                'icon'  => '',
                'label' => _p('cancelled_members'),
                'link'  => $oUrl->makeUrl('admincp.user.cancellations.feedback'),
                'tags'  => 'admincp.member.cancellations',
            ],
            'search_ip_address'    => [
                'icon'  => '',
                'label' => _p('search_ip_address'),
                'link'  => $oUrl->makeUrl('admincp.core.ip'),
                'tags'  => 'admincp.member.search_ip_address',
            ],
        ]);

        $this->aMenus['member'] = [
            'icon'  => 'ico ico-user-circle-o',
            'label' => _p('members'),
            'link'  => '#',
            'items' => $items,
        ];
    }

    /**
     * @return $this
     */
    public function prepare()
    {
        $tags = $this->activeTags;

        if (!$tags) {
            $tags = $this->defaultTags;
        }

        foreach ($this->aMenus as $index => $aMainMenu) {
            if (isset($aMainMenu['tags']) && $aMainMenu['tags'] == $tags) {
                $this->aMenus[$index]['is_active'] = 1;
            }

            if (!isset($aMainMenu['items'])) {
                continue;
            }

            foreach ($aMainMenu['items'] as $key => $aMenu) {
                if (isset($aMenu['tags']) && $aMenu['tags'] == $tags) {
                    $this->aMenus[$index]['items'][$key]['is_active'] = 1;
                    $this->aMenus[$index]['is_active'] = 1;
                    return $this;
                }
            }
        }
        return $this;
    }

    /**
     * Get menus
     *
     * @return array
     */
    public function get()
    {

//        _dump($this->aMenus);
        return $this->aMenus;
    }

    public function initLogoutMenus()
    {
        $this->aMenus['logout'] = [
            'icon'  => 'ico ico-signout',
            'label' => _p('logout'),
            'link'  => Phpfox::getLib('url')->makeUrl('logout'),
        ];
    }
}