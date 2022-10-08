<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Storage;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter as Adapter;

/**
 * Class DigitalOceanSpaceAdapter
 * @package Core\Storage
 */
class DigitalOceanSpaceAdapter extends Adapter implements StorageAdapterInterface
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
            'cdn_enabled' => false,
            'cdn_base_url' => '',
            'prefix' => '',
        ], $params);

        $endpoint = sprintf("https://%s.digitaloceanspaces.com", $params['region']);

        if ($params['cdn_enabled'] && $params['cdn_base_url']) {
            $this->baseUrl = rtrim($params['cdn_base_url'], '/') . '/';
        } else {
            $this->baseUrl = sprintf("https://%s.%s.digitaloceanspaces.com/", $params['bucket'], $params['region']);
        }

        $options = [];
        if (!empty($params['metadata'])
            && version_compare(phpversion(), '7.1') >= 0) {
            foreach ($params['metadata'] as $key => $value) {
                if (isset($value) && $value != '') {
                    $options[$key] = $value;
                }
            }
        }

        $s3Client = new S3Client([
            'credentials' => [
                'key' => $params['key'],
                'secret' => $params['secret']
            ],
            'endpoint' => $endpoint,
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