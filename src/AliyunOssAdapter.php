<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 8/23/2018
 * Time: 10:02 AM
 */

namespace JimChen\Flysystem\AliyunOss;

use OSS\OssClient;
use GuzzleHttp\Psr7;
use League\Flysystem\Util;
use OSS\Core\OssException;
use League\Flysystem\Config;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\CanOverwriteFiles;

class AliyunOssAdapter extends AbstractAdapter implements CanOverwriteFiles
{
    /**
     * @var OssClient
     */
    protected $ossClient;

    /**
     * @var string
     */
    protected $bucket;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * Constructor.
     *
     * @param OssClient $ossClient
     * @param string    $bucket
     * @param string    $prefix
     * @param array     $options
     */
    public function __construct(OssClient $ossClient, $bucket, $prefix = '', array $options = [])
    {
        $this->ossClient = $ossClient;
        $this->bucket = $bucket;
        $this->setPathPrefix($prefix);
        $this->options = $options;
    }

    /**
     * Get the OssClient bucket.
     *
     * @return string
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * Set the OssClient bucket.
     *
     * @param string $bucket
     */
    public function setBucket($bucket)
    {
        $this->bucket = $bucket;
    }

    /**
     * Get the OssClient instance.
     *
     * @return OssClient
     */
    public function getClient()
    {
        return $this->ossClient;
    }

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array
     */
    public function write($path, $contents, Config $config)
    {
        return $this->upload($path, $contents, $config);
    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array
     */
    public function update($path, $contents, Config $config)
    {
        return $this->upload($path, $contents, $config);
    }

    /**
     * Write a new file using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function writeStream($path, $resource, Config $config)
    {
        return $this->upload($path, $resource, $config);
    }

    /**
     * Update a file using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->upload($path, $resource, $config);
    }

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function rename($path, $newpath)
    {
        if (!$this->copy($path, $newpath)) {
            return false;
        }

        return $this->delete($path);
    }

    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function copy($path, $newpath)
    {
        try {
            $this->ossClient->copyObject(
                $this->bucket,
                $this->applyPathPrefix($path),
                $this->bucket,
                $this->applyPathPrefix($newpath),
                $this->options
            );
        } catch (OssException $e) {
            return false;
        }

        return true;
    }

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function delete($path)
    {
        $object = $this->applyPathPrefix($path);

        $this->ossClient->deleteObject($this->bucket, $object, $this->options);

        return !$this->has($path);
    }

    /**
     * Delete a directory.
     *
     * @param string $dirname
     *
     * @return bool
     */
    public function deleteDir($dirname)
    {
        return false;
    }

    /**
     * Create a directory.
     *
     * @param string $dirname directory name
     * @param Config $config
     *
     * @return array|false
     */
    public function createDir($dirname, Config $config)
    {
        try {
            $key = $this->applyPathPrefix($dirname);
            $options = $this->getOptionsFromConfig($config);

            $response = $this->ossClient->createObjectDir($this->bucket, rtrim($key, '/'), $options);
        } catch (OssException $e) {
            return false;
        }

        return $this->normalizeResponse($response, $key);
    }

    /**
     * Set the visibility for a file.
     *
     * @param string $path
     * @param string $visibility
     *
     * @return array|false file meta data
     */
    public function setVisibility($path, $visibility)
    {
        try {
            $this->ossClient->putObjectAcl(
                $this->bucket,
                $this->applyPathPrefix($path),
                in_array($visibility, OssClient::$OSS_ACL_TYPES) ? $visibility : 'default'
            );
        } catch (OssException $e) {
            return false;
        }

        return compact('path', 'visibility');
    }

    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return bool
     */
    public function has($path)
    {
        $location = $this->applyPathPrefix($path);

        if ($this->ossClient->doesObjectExist($this->bucket, $location, $this->options)) {
            return true;
        }

        return $this->doesDirectoryExist($location);
    }

    /**
     * Read a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function read($path)
    {
        try {
            $response = $this->ossClient->getObject($this->bucket, $this->applyPathPrefix($path));

            return $this->normalizeResponse([
                'contents' => $response,
            ], $path);
        } catch (OssException $e) {
            return false;
        }
    }

    /**
     * Read a file as a stream.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function readStream($path)
    {
        try {
            $object = $this->applyPathPrefix($path);
            $url = $this->ossClient->signUrl($this->bucket, $object, 60, OssClient::OSS_HTTP_GET, $this->options);
            $handle = fopen($url, 'r');

            return $this->normalizeResponse([
                'stream' => $handle,
            ], $path);
        } catch (OssException $e) {
            return false;
        }
    }

    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool   $recursive
     *
     * @return array|false
     */
    public function listContents($directory = '', $recursive = false)
    {
        $prefix = $this->applyPathPrefix(rtrim($directory, '/') . '/');

        $options = [
            OssClient::OSS_PREFIX    => ltrim($prefix, '/'),
            OssClient::OSS_MARKER    => '',
            OssClient::OSS_MAX_KEYS  => 100,
        ];

        if ($recursive) {
            $options[OssClient::OSS_DELIMITER] = '';
        } else {
            $options[OssClient::OSS_DELIMITER] = '/';
        }

        try {
            list($response, $path) = $this->retrieveListing($options);
        } catch (OssException $e) {
            return false;
        }

        $normalizer = [$this, 'normalizeResponse'];
        $normalized = array_map($normalizer, $response, $path);

        return Util::emulateDirectories($normalized);
    }

    /**
     * @param array $options
     *
     * @return array
     * @throws OssException
     */
    protected function retrieveListing(array $options)
    {
        $result = $this->ossClient->listObjects($this->bucket, $options);
        $listing = [
            'response' => [],
            'path'     => [],
        ];

        foreach ($result->getObjectList() as $object) {
            $listing['response'][] = [
                'object' => [
                    'key'           => $object->getKey(),
                    'last_modified' => $object->getLastModified(),
                    'etag'          => $object->getETag(),
                    'type'          => $object->getType(),
                    'size'          => $object->getSize(),
                    'storage_class' => $object->getStorageClass(),
                ],
            ];
            $listing['path'][] = $object->getKey();
        }

        foreach ($result->getPrefixList() as $object) {
            $listing['response'][] = [
                'object' => [
                    'prefix' => $object->getPrefix(),
                ],
            ];
            $listing['path'][] = $object->getPrefix();
        }

        return array_values($listing);
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMetadata($path)
    {
        $response = $this->getObjectMeta($path);

        if (empty($response)) {
            return false;
        }

        return $this->normalizeResponse($response, $path);
    }

    /**
     * Get the size of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getSize($path)
    {
        if ($metaData = $this->getMetadata($path)) {
            return $this->normalizeResponse([
                'size' => $metaData['content-length'],
            ], $path);
        }

        return false;
    }

    /**
     * Get the mimetype of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMimetype($path)
    {
        if ($metaData = $this->getMetadata($path)) {
            $contentType = array_key_exists('content_type', $metaData) ? $metaData['content_type'] : $metaData['info']['content_type'];

            return [
                'mimetype' => $contentType,
            ];
        }

        return false;
    }

    /**
     * Get the last modified time of a file as a timestamp.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getTimestamp($path)
    {
        if ($metaData = $this->getMetadata($path)) {
            return $this->normalizeResponse([
                'timestamp' => strtotime($metaData['last-modified']),
            ], $path);
        }

        return false;
    }

    /**
     * Get the visibility of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getVisibility($path)
    {
        try {
            return [
                'visibility' => $this->ossClient->getObjectAcl($this->bucket, $this->applyPathPrefix($path)),
            ];
        } catch (OssException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function applyPathPrefix($path)
    {
        return ltrim(parent::applyPathPrefix($path), '/');
    }

    /**
     * {@inheritdoc}
     */
    public function setPathPrefix($prefix)
    {
        $prefix = ltrim($prefix, '/');

        parent::setPathPrefix($prefix);
    }

    /**
     * Get the object meta.
     *
     * @param $path
     *
     * @return array|bool
     */
    protected function getObjectMeta($path)
    {
        try {
            return $this->ossClient->getObjectMeta($this->bucket, $this->applyPathPrefix($path));
        } catch (OssException $e) {
            if ($e->getHTTPStatus() === 404) {
                return false;
            }

            throw $e;
        }
    }

    /**
     * Upload an object.
     *
     * @param string $path
     * @param string $body
     * @param Config $config
     *
     * @return array|false
     * @throws \InvalidArgumentException
     */
    protected function upload($path, $body, Config $config)
    {
        $key = $this->applyPathPrefix($path);

        try {
            $options = $this->getOptionsFromConfig($config, [
                OssClient::OSS_LENGTH,
                OssClient::OSS_CONTENT_TYPE,
                OssClient::OSS_CALLBACK,
            ]);
            $response = $this->ossClient->putObject($this->bucket, $key, Psr7\stream_for($body)->getContents(),
                $options);
        } catch (OssException $e) {
            return false;
        }

        return $this->normalizeResponse($response, $key);
    }

    /**
     * Get options from the config.
     *
     * @param Config $config
     * @param array  $keys
     *
     * @return array
     */
    protected function getOptionsFromConfig(Config $config, $keys = [])
    {
        $options = $this->options;

        foreach ((array)$keys as $key) {
            if ($value = $config->get($key)) {
                $options[$key] = $value;
            }
        }

        return $options;
    }

    /**
     * Normalize the object result array.
     *
     * @param array $response
     * @param null  $path
     *
     * @return array
     */
    protected function normalizeResponse(array $response, $path = null)
    {
        $result = [
            'path' => $path ?: $this->removePathPrefix($path),
        ];

        $result = array_merge($result, Util::pathinfo($result['path']));

        if (substr($result['path'], -1) === '/') {
            $result['type'] = 'dir';
            $result['path'] = rtrim($result['path'], '/');

            return $result;
        }

        return array_merge($result, $response, [
            'type' => 'file',
        ]);
    }

    /**
     * @param $location
     *
     * @return bool
     */
    protected function doesDirectoryExist($location)
    {
        // Maybe this isn't an actual key, but a prefix.
        // Do a prefix listing of objects to determine.
        try {
            $result = $this->ossClient->listObjects($this->bucket, [
                OssClient::OSS_PREFIX   => rtrim($location, '/') . '/',
                OssClient::OSS_MAX_KEYS => 1,
            ]);

            return $result->getObjectList() || $result->getPrefixList();
        } catch (OssException $e) {
            if ($e->getHTTPStatus() === 403) {
                return false;
            }

            throw $e;
        }
    }
}
