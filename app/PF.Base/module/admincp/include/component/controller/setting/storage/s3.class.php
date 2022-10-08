<?php

/**
 * Class Admincp_Component_Controller_Setting_Storage_S3
 * @since 4.8.0
 * @author phpfox
 */
class Admincp_Component_Controller_Setting_Storage_S3 extends Phpfox_Component
{
    const SERVICE_ID = 's3';

    public function process()
    {
        $sError = null;
        $manager = Phpfox::getLib('storage.admincp');
        $storage_id = $this->request()->get('storage_id');
        $bIsEdit = !$storage_id;
        $aValidation = array(
            'storage_name' => array(
                'def' => 'string:required',
                'title' => _p('storage_name_is_required')
            ),
            'key' => array(
                'def' => 'string:required',
                'title' => _p('amazon_key_id_is_required')
            ),
            'secret' => array(
                'def' => 'string:required',
                'title' => _p('amazon_secret_key_is_required')
            ),
            'bucket' => array(
                'def' => 'string:required',
                'title' => _p('bucket_is_required')
            ),
            'region' => array(
                'def' => 'string:required',
                'title' => _p('region_is_required')
            ),

        );

        $oValid = Phpfox::getLib('validator')->set(array(
                'sFormName' => 'js_storage_s3_form',
                'aParams' => $aValidation
            )
        );
        $aVals = $this->request()->get('val');
        $aMetadataList = $this->_getWriableMetaDataList();

        if (!empty($aVals) && $oValid->isValid($aVals)) {
            $bIsActive = !!$aVals['is_active'];
            $bIsDefault = !!$aVals['is_default'];

            if ($bIsDefault) {
                $bIsActive = true;
            }
            $bIsValid = true;
            $bCloudFrontEnable = !!$aVals['cloudfront_enabled'];
            if ($bCloudFrontEnable && empty($aVals['cloudfront_url'])) {
                $sError = _p('cloudfront_url_is_required_if_cloudfront_is_enabled');
                $bIsValid = false;
            }
            $config = [
                'key' => $aVals['key'],
                'secret' => $aVals['secret'],
                'bucket' => $aVals['bucket'],
                'region' => $aVals['region'],
                'cloudfront_url' => isset($aVals['cloudfront_url']) ? $aVals['cloudfront_url'] : '',
                'cloudfront_enabled' => $bCloudFrontEnable,
                'prefix' => $aVals['prefix'],
                'metadata' => !empty($aVals['metadata']) ? $aVals['metadata'] : [],
            ];

            if ($bIsActive) {
                try {
                    $bIsValid = $manager->verifyStorageConfig(self::SERVICE_ID, $config);
                    if (!$bIsValid) {
                        $sError = _p('invalid_configuration');
                    }

                } catch (Exception $exception) {
                    $bIsValid = false;
                    $sError = $exception->getMessage();
                }
            }

            if ($bIsValid) {
                $storage_name = isset($aVals['storage_name']) ? $aVals['storage_name'] : '';
                if ($storage_id) {
                    $manager->updateStorageConfig($storage_id, self::SERVICE_ID, $storage_name, $bIsDefault, $bIsActive, $config);
                    Phpfox::addMessage(_p('Your changes have been saved!'));
                } else {
                    $manager->createStorage($storage_id, self::SERVICE_ID, $storage_name, $bIsDefault, $bIsActive, $config);
                    Phpfox::addMessage(_p('Your changes have been saved!'));
                    Phpfox::getLib('url')->send('admincp.setting.storage.manage');
                }
            }
        } else if ($storage_id) {
            $aVals = $manager->getStorageConfig($storage_id);
        } elseif (Phpfox::isAppActive('PHPfox_AmazonS3')) {
            $aVals = [
                'storage_name' => 'Amazon S3',
                'key' => Phpfox::getParam('amazons3.cdn_amazon_id', ''),
                'secret' => Phpfox::getParam('amazons3.cdn_amazon_secret', ''),
                'bucket' => Phpfox::getParam('amazons3.cdn_bucket', ''),
                'region' => Phpfox::getParam('amazons3.cdn_region', ''),
                'cloudfront_url' => Phpfox::getParam('amazons3.amazon_cloudfront_url', ''),
                'cloudfront_enabled' => !!Phpfox::getParam('amazons3.amazon_cloudfront_url', ''),
                'prefix' => '',
            ];
        } else {
            $aVals = [
                'storage_name' => 'Amazon S3'
            ];
        }

        if (!empty($aVals['metadata'])) {
            foreach ($aVals['metadata'] as $key => $value) {
                if (isset($aMetadataList[$key])) {
                    $aMetadataList[$key]['value'] = $value;
                }
            }
        }

        $this->template()
            ->clearBreadCrumb()
            ->setBreadCrumb(_p('storage_system'), $this->url()->makeUrl('admincp.setting.storage.manage'));

        if ($bIsEdit) {
            $this->template()
                ->setBreadCrumb(_p('add_storage'), $this->url()->makeUrl('admincp.setting.storage.add'));
        }

        $this->template()
            ->setTitle(_p('aws_s3'))
            ->setBreadCrumb(_p('aws_s3'))
            ->setActiveMenu('admincp.setting.storage')
            ->assign([
                'sCreateJs' => $oValid->createJS(),
                'sGetJsForm' => $oValid->getJsForm(),
                'aForms' => $aVals,
                'sError' => $sError,
                'aMetaDataList' => $aMetadataList,
            ]);
    }

    private function _getWriableMetaDataList()
    {
        return [
            'CacheControl' => [
                'title' => 'Cache-Control',
                'name' => 'CacheControl',
            ],
            'ContentDisposition' => [
                'title' => 'Content-Disposition',
                'name' => 'ContentDisposition',
            ],
            'ContentEncoding' => [
                'title' => 'Content-Encoding',
                'name' => 'ContentEncoding',
            ],
            'ContentLanguage' => [
                'title' => 'Content-Language',
                'name' => 'ContentLanguage',
            ],
            'Expires' => [
                'title' => 'Expires',
                'name' => 'Expires'
            ],
            'WebsiteRedirectLocation' => [
                'title' => 'x-amz-website-redirect-location',
                'name' => 'WebsiteRedirectLocation',
            ],
        ];
    }
}