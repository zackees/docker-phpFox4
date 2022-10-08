<?php
/**
 * Class Admincp_Component_Controller_Setting_Logger_View
 * @since 4.8.0
 * @author phpfox
 */
class Admincp_Component_Controller_Setting_Logger_View extends Phpfox_Component
{
    public function process()
    {
        $requestObject = $this->request();
        $logAdmincp = Phpfox::getLib('log.admincp');
        $service = $requestObject->get('service', 'local');
        $channel = $requestObject->get('channel');
        $limit = 10;
        $page = $requestObject->get('page', 1);
        $supportedChannels = $logAdmincp->getSupportedChannelsByService($service);
        $rows = [];

        if (empty($channel) && !empty($supportedChannels)) {
            $channel = $supportedChannels[0];
        }

        if (!empty($channel)) {
            list($count, $rows) = $logAdmincp->getLogs($service, $channel, $page, $limit);

            Phpfox::getLib('pager')->set([
                'page' => $page,
                'size' => $limit,
                'count' => $count,
            ]);
        }
        
        $this->template()->setTitle(_p('log_viewer'))
            ->clearBreadCrumb()
            ->setBreadCrumb(_p('log_viewer'))
            ->setHeader('cache', [
                'logger.js' => 'module_admincp'
            ])
            ->assign([
                'supportedServices' => $logAdmincp->getSupportedServicesForView(),
                'supportedChannels' => $supportedChannels,
                'selectedService'=> $service,
                'selectedChannel' => $channel,
                'logItems' => $rows,
            ]);
    }
}