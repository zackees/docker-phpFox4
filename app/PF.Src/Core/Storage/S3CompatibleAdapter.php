<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Storage;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter as Adapter;

/**
 * Class S3CompatibleAdapter
 * @package Core\Storage
 */
class S3CompatibleAdapter extends Adapter implements StorageAdapterInterface
{

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var string
     */
    private $region;

    /**
     * AwsS3V3Adapter constructor.
     * @param $params
     */
    public function __construct($params)
    {
        $params = array_merge([
            'bucket' => '',
            'key' => '',
            'secret' => '',
            'version' => 'latest',
            'endpoint' => '',
            'base_url'=> '',
            'cdn_enabled' => false,
            'cdn_base_url' => '',
            'prefix' => '',
        ], $params);

        $this->baseUrl = rtrim($params['cdn_enabled'] && $params['cdn_base_url'] ? $params['cdn_base_url'] : $params['base_url'], '/') . '/';

        $options = [];
        $s3Client = new S3Client([
            'credentials' => [
                'key' => $params['key'],
                'secret' => $params['secret']
            ],
            'endpoint' => !empty($params['endpoint']) ? $params['endpoint'] : null,
            'region' => $params['region'],
            'version' => $params['version'],
        ]);

        $this->region = $params['region'];

        parent::__construct($s3Client, $params['bucket'], $params['prefix'], $options);
    }

    /**
     * @inheritDoc
     */
    public function getUrl($path)
    {
        return $this->baseUrl . $this->applyPathPrefix($path);
    }

    public function isValid()
    {
        return $this->s3Client->listBuckets();
    }
}