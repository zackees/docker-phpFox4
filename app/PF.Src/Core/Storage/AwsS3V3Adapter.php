<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Storage;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter as Adapter;

/**
 * Class AwsS3V3Adapter
 * @package Core\Storage
 */
class AwsS3V3Adapter extends Adapter implements StorageAdapterInterface
{
    /**
     * @var bool
     */
    private $cloudfrontEnabled;

    /**
     * @var string
     */
    private $pathFile;

    /**
     * @var string
     */
    private $cloudFrontUrl;

    /**
     * @var string
     */
    private $region;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var string
     */
    private $prefix;

    /**
     * AwsS3V3Adapter constructor.
     * @param $params
     */
    public function __construct($params)
    {
        $params = array_merge([
            'cloudfront_enabled' => false,
            'cloudfront_url' => '',
            'bucket' => '',
            'prefix' => '',
            'key' => 's3-key',
            'secret' => 's3-secret',
            'version' => 'latest',
        ], $params);

        $options = [];
        if (!empty($params['metadata'])
            && version_compare(phpversion(), '7.1') >= 0) {
            foreach ($params['metadata'] as $key => $value) {
                if (isset($value) && $value != '') {
                    $options[$key] = $value;
                }
            }
        }

        extract($params, EXTR_OVERWRITE);

        $s3Client = new S3Client([
            'credentials' => [
                'key' => $params['key'],
                'secret' => $params['secret']
            ],
            'region' => $params['region'],
            'version' => $params['version'],
            'scheme' => PHPFOX_IS_HTTPS ? 'https' : 'http'
        ]);

        $this->region = $params['region'];

        $this->cloudFrontUrl = $params['cloudfront_url'] ? rtrim($params['cloudfront_url'], '/') . '/' : '';
        $this->cloudfrontEnabled = !!$params['cloudfront_enabled'] && $this->cloudFrontUrl;

        $this->cloudFrontUrl = rtrim($this->cloudFrontUrl, '/') . '/';

        parent::__construct($s3Client, $params['bucket'], $params['prefix'], $options);

    }

    /**
     * @inheritDoc
     */
    public function getUrl($path)
    {
        if (!$this->baseUrl) {
            $this->baseUrl = rtrim($this->s3Client->getObjectUrl($this->getBucket(), '/'), '/') . '/';
        }

        $key = $this->applyPathPrefix(str_replace($this->pathFile, '', $path));

        if ($this->cloudfrontEnabled && $this->cloudFrontUrl) {
            return $this->cloudFrontUrl . $key;
        }

        if (!empty($key)) {
            $baseUrl = $this->s3Client->getObjectUrl($this->getBucket(), $key);
        } else {
            $baseUrl = $this->baseUrl;
        }

        return $baseUrl;
    }

    public function isValid()
    {
        $currentBucket = $this->getBucket();

        if ($this->s3Client->doesBucketExist($currentBucket) === true) {
            return true;
        }

        $result = $this->s3Client->createBucket(['Bucket' => $currentBucket]);

        if ($result && $result->get('Location')) {
            return true;
        }

        return false;
    }
}