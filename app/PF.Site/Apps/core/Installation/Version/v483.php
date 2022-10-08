<?php
namespace Apps\PHPfox_Core\Installation\Version;

use Phpfox;

class v483
{
    public function process()
    {
        $this->_initMessageQueueTitle();
        $this->_initStaticPageComponents();
    }

    private function _initStaticPageComponents()
    {
        $pages = db()->select('title_url, is_active')
            ->from(':page')
            ->executeRows();
        if (!empty($pages)) {
            $existedComponents = db()->select('component')
                ->from(':component')
                ->where([
                    'module_id' => 'page',
                    'AND component IN ("' . implode('","', array_column($pages, 'title_url')) . '")',
                ])->executeRows();
            if (!empty($existedComponents)) {
                $urls = array_column($existedComponents, 'component');
                $existedComponents = array_combine($urls, $urls);
            }
            $multiInsert = [];
            foreach ($pages as $page) {
                if (!isset($existedComponents[$page['title_url']])) {
                    $multiInsert[] = [
                        'component' => $page['title_url'],
                        'm_connection' => 'page.' . $page['title_url'],
                        'module_id' => 'page',
                        'is_controller' => 1,
                        'is_block' => 0,
                        'is_active' => !empty($page['is_active']),
                    ];
                }
            }

            if (!empty($multiInsert)) {
                db()->multiInsert(Phpfox::getT('component'), ['component', 'm_connection', 'module_id', 'is_controller', 'is_block', 'is_active'], $multiInsert);
            }
        }
    }

    public function _initMessageQueueTitle()
    {
        $cronTable = Phpfox::getT('cron');
        if (!db()->isField($cronTable, 'name')) {
            db()->addField([
                'table' => $cronTable,
                'field' => 'name',
                'type' => 'VCHAR:255',
                'null' => true,
                'default' => 'NULL'
            ]);
        }

        $mappingNames = [
            'Phpfox_Queue::instance()->work();' => 'Executing Message Queues',
            'Phpfox::getService(\'core.temp-file\')->clean();' => 'Remove Unused Files',
            'Phpfox::getService(\'admincp.maintain\')->cronRemoveCache();' => 'Auto Clear Caches',
            'log' => 'Remove Old User Activity Sessions',
            'photo' => 'Remove Unused Photos',
            'subscribe' => 'Downgrade Expired Subscribers',
            'marketplace' => 'Send Notifications for Expired Listings',
            'shoutbox' => 'Remove Old Shoutbox Messages',
            'restful_api' => 'Remove Expired Access Tokens',
            'mobile' => 'Executing Mobile Push Notifications',
        ];
        $moduleIds = ['core', 'log', 'photo', 'subscribe', 'marketplace', 'shoutbox', 'restful_api', 'mobile'];
        $updateItems = db()->select('cron_id, module_id, php_code')
            ->from($cronTable)
            ->where([
                'module_id' => ['in' => '"' . implode('","', $moduleIds) . '"'],
                'AND name IS NULL',
            ])->executeRows(false);
        foreach ($updateItems as $updateItem) {
            $updatedValue = isset($mappingNames[$updateItem['module_id']]) ? $mappingNames[$updateItem['module_id']]
                : (isset($mappingNames[trim($updateItem['php_code'])]) ? $mappingNames[trim($updateItem['php_code'])] : null);
            if (!empty($updatedValue)) {
                db()->update($cronTable, ['name' => $updatedValue], ['cron_id' => $updateItem['cron_id']]);
            }
        }
    }
}