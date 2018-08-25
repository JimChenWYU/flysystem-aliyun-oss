<?php

namespace spec\JimChen\Flysystem\AliyunOss;

use JimChen\Flysystem\AliyunOss\AliyunOssAdapter;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use OSS\Core\OssException;
use OSS\Core\OssUtil;
use OSS\Model\ObjectInfo;
use OSS\Model\ObjectListInfo;
use OSS\Model\PrefixInfo;
use OSS\OssClient;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class AliyunOssAdapterSpec extends ObjectBehavior
{
    /**
     * @var \OSS\OssClient
     */
    private $client;
    private $bucket;
    const PATH_PREFIX = 'path-prefix';

    /**
     * @param \OSS\OssClient $client
     */
    public function let($client)
    {
        $this->client = $client;
        $this->bucket = 'bucket';
        $this->beConstructedWith($this->client, $this->bucket, self::PATH_PREFIX);
    }

    public function it_should_upload_exception()
    {
        $key = 'dir/name/fun.avi';
        $this->client->putObject(
            $this->bucket,
            self::PATH_PREFIX . '/' . $key,
            'contents',
            Argument::type('array')
        )->willThrow(returnOssException('RequestTimeout'));

        $this->write($key, 'contents', new Config())->shouldBe(false);
    }

    public function it_should_get_list_contents()
    {
        $result = new ObjectListInfo(
            $this->bucket,
            self::PATH_PREFIX . '/',
            '',
            OssUtil::decodeKey('', ''),
            100,
            '/',
            '',
            [
                new ObjectInfo(
                    'fun/movie/001.avi',
                    '2012-02-24T08:43:07.000Z',
                    '&quot;5B3C1A2E053D763E1B002CC607C5A0FE&quot;',
                    'Normal',
                    344606,
                    'Standard'),
                new ObjectInfo(
                    'fun/movie/007.avi',
                    '2012-02-24T08:43:27.000Z',
                    '&quot;5B3C1A2E053D763E1B002CC607C5A0FE&quot;',
                    'Normal',
                    344606,
                    'Standard'),
                new ObjectInfo(
                    'fun/movie/007.avi',
                    '2012-02-24T08:43:27.000Z',
                    '&quot;5B3C1A2E053D763E1B002CC607C5A0FE&quot;',
                    'Normal',
                    344606,
                    'Standard'),
            ],
            [
                new PrefixInfo(
                    'fun/movie/'
                ),
            ]
        );
        $this->client->listObjects($this->bucket, [
            OssClient::OSS_PREFIX    => self::PATH_PREFIX . '/',
            OssClient::OSS_DELIMITER => '/',
            OssClient::OSS_MARKER    => '',
            OssClient::OSS_MAX_KEYS  => 100,
        ])->shouldBeCalled()->willReturn($result);

        $this->listContents('/', false);
    }

    public function it_should_get_list_contents_with_recursive()
    {
        $result = new ObjectListInfo(
            $this->bucket,
            self::PATH_PREFIX . '/',
            '',
            OssUtil::decodeKey('', ''),
            100,
            '/',
            '',
            [
                new ObjectInfo(
                    'fun/movie/001.avi',
                    '2012-02-24T08:43:07.000Z',
                    '&quot;5B3C1A2E053D763E1B002CC607C5A0FE&quot;',
                    'Normal',
                    344606,
                    'Standard'),
                new ObjectInfo(
                    'fun/movie/007.avi',
                    '2012-02-24T08:43:27.000Z',
                    '&quot;5B3C1A2E053D763E1B002CC607C5A0FE&quot;',
                    'Normal',
                    344606,
                    'Standard'),
                new ObjectInfo(
                    'fun/movie/007.avi',
                    '2012-02-24T08:43:27.000Z',
                    '&quot;5B3C1A2E053D763E1B002CC607C5A0FE&quot;',
                    'Normal',
                    344606,
                    'Standard'),
            ],
            [
                new PrefixInfo(
                    'fun/movie/'
                ),
            ]
        );
        $this->client->listObjects($this->bucket, [
            OssClient::OSS_PREFIX    => self::PATH_PREFIX . '/',
            OssClient::OSS_DELIMITER => '',
            OssClient::OSS_MARKER    => '',
            OssClient::OSS_MAX_KEYS  => 100,
        ])->shouldBeCalled()->willReturn($result);

        $this->listContents('/', true);
    }

    public function it_should_get_list_contents_return_false()
    {
        $this->client->listObjects($this->bucket, [
            OssClient::OSS_PREFIX    => self::PATH_PREFIX . '/',
            OssClient::OSS_DELIMITER => '/',
            OssClient::OSS_MARKER    => '',
            OssClient::OSS_MAX_KEYS  => 100,
        ])->willThrow(returnOssException('RequestTimeout'));

        $this->listContents('/', false)->shouldBe(false);
    }

    public function it_should_add_path_prefix_without_left_directory_separator()
    {
        $this->applyPathPrefix('/path')->shouldBe(self::PATH_PREFIX . '/path');
    }

    public function it_should_set_path_prefix_without_left_directory_separator()
    {
        $this->setPathPrefix('/prefix');
        $this->getPathPrefix()->shouldBe('prefix/');
    }

    public function it_should_retrieve_the_bucket()
    {
        $this->getBucket()->shouldBe('bucket');
    }

    public function it_should_set_the_bucket()
    {
        $this->setBucket('newbucket');
        $this->getBucket()->shouldBe('newbucket');
    }

    public function it_should_retrieve_the_client()
    {
        $this->getClient()->shouldBe($this->client);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(AliyunOssAdapter::class);
        $this->shouldHaveType(AdapterInterface::class);
    }

    public function it_should_write_files()
    {
        $this->make_it_write_using('write', 'contents');
    }

    public function it_should_update_files()
    {
        $this->make_it_write_using('update', 'contents');
    }

    public function it_should_write_files_streamed()
    {
        $config = new Config();
        $key = 'key.txt';
        $this->client->putObject(
            $this->bucket,
            self::PATH_PREFIX . '/' . $key,
            \GuzzleHttp\Psr7\stream_for(tmpfile()),
            Argument::type('array')
        )->willReturn(['body' => '']);

        $this->writeStream($key, tmpfile(), $config)->shouldBeArray();
    }

    public function it_should_update_files_streamed()
    {
        $config = new Config();
        $key = 'key.txt';
        $this->client->putObject(
            $this->bucket,
            self::PATH_PREFIX . '/' . $key,
            \GuzzleHttp\Psr7\stream_for(tmpfile()),
            Argument::type('array')
        )->willReturn(['body' => '']);

        $this->updateStream($key, tmpfile(), $config)->shouldBeArray();
    }

    public function it_should_delete_files()
    {
        $key = 'key.txt';
        $this->client->deleteObject($this->bucket, self::PATH_PREFIX . '/' . $key,
            Argument::type('array'))->shouldBeCalled();

        $this->make_it_404_on_has_object($key);

        $this->delete($key)->shouldBe(true);
    }

    public function it_should_read_a_file()
    {
        $this->make_it_read_a_file('read', 'contents');
    }

    public function it_should_read_a_file_stream()
    {
        $resource = tmpfile();
        $this->make_it_read_a_file('readStream', $resource);
        fclose($resource);
    }

    public function it_read_a_file_return_false()
    {
        $resource = tmpfile();
        $this->make_it_read_a_file_return_false('read', 'contents');
        fclose($resource);
    }

    public function it_read_a_file_stream_return_false()
    {
        $resource = tmpfile();
        $this->make_it_read_a_file_return_false('readStream', $resource);
        fclose($resource);
    }

    public function it_should_return_when_trying_to_read_an_non_existing_file()
    {
        $key = 'key.txt';
        $this->client->getObject($this->bucket, self::PATH_PREFIX . '/' . $key)
            ->willThrow(returnOssException('NoSuchKey', 404));

        $this->read($key)->shouldBe(false);
    }

    public function it_should_retrieve_all_file_metadata()
    {
        $this->make_it_retrieve_file_metadata('getMetadata');
        $this->make_it_retrieve_file_metadata_return_false('getMetadata');
    }

    public function it_should_retrieve_the_timestamp_of_a_file()
    {
        $this->make_it_retrieve_file_metadata('getTimestamp');
        $this->make_it_retrieve_file_metadata_return_false('getTimestamp');
    }

    public function it_should_retrieve_the_mimetype_of_a_file()
    {
        $this->make_it_retrieve_file_metadata('getMimetype');
        $this->make_it_retrieve_file_metadata_return_false('getMimetype');
    }
    
    public function it_should_retrieve_the_size_of_a_file()
    {
        $this->make_it_retrieve_file_metadata('getSize');
        $this->make_it_retrieve_file_metadata_return_false('getSize');
    }

    public function it_should_return_true_when_object_exists()
    {
        $key = 'key.txt';
        $this->client->doesObjectExist($this->bucket, self::PATH_PREFIX . '/' . $key,
            Argument::type('array'))->willReturn(true);

        $this->has($key)->shouldBe(true);
    }

    public function it_should_return_true_when_prefix_exists()
    {
        $key = 'directory';
        $result = new ObjectListInfo(
            $this->bucket,
            self::PATH_PREFIX . '/',
            '',
            OssUtil::decodeKey('', ''),
            1,
            '',
            '',
            [
                new ObjectInfo(
                    'fun/movie/001.avi',
                    '2012-02-24T08:43:07.000Z',
                    '&quot;5B3C1A2E053D763E1B002CC607C5A0FE&quot;',
                    'Normal',
                    344606,
                    'Standard'),
            ],
            [
                new PrefixInfo(
                    'fun/movie/'
                ),
            ]
        );

        $this->client->doesObjectExist($this->bucket, self::PATH_PREFIX . '/' . $key,
            Argument::type('array'))->willReturn(false);
        $this->client->listObjects($this->bucket, [
            OssClient::OSS_PREFIX   => self::PATH_PREFIX . '/' . $key . '/',
            OssClient::OSS_MAX_KEYS => 1,
        ])->willReturn($result);

        $this->has($key)->shouldBe(true);
    }

    public function it_should_return_false_when_listing_objects_returns_a_403()
    {
        $key = 'directory';

        $this->client->doesObjectExist($this->bucket, self::PATH_PREFIX . '/' . $key, Argument::type('array'))
            ->willReturn(false);

        $this->client->listObjects($this->bucket, [
            OssClient::OSS_PREFIX   => self::PATH_PREFIX . '/' . $key . '/',
            OssClient::OSS_MAX_KEYS => 1,
        ])->willThrow(returnOssException('', 403));

        $this->has($key)->shouldBe(false);
    }

    public function it_should_pass_through_when_listing_objects_throws_an_exception()
    {
        $key = 'directory';

        $exception = returnOssException('', 500);
        $this->client->doesObjectExist($this->bucket, self::PATH_PREFIX . '/' . $key, Argument::type('array'))
            ->willReturn(false);

        $this->client->listObjects($this->bucket, [
            OssClient::OSS_PREFIX   => self::PATH_PREFIX . '/' . $key . '/',
            OssClient::OSS_MAX_KEYS => 1,
        ])->willThrow($exception);

        $this->shouldThrow($exception)->duringHas($key);
    }
    
    public function it_should_copy_files()
    {
        $sourceKey = 'key.txt';
        $key = 'newkey.txt';
        $this->make_it_copy_successfully($key, $sourceKey);
        $this->copy($sourceKey, $key)->shouldBe(true);
    }

    public function it_should_return_false_when_copy_fails()
    {
        $sourceKey = 'key.txt';
        $key = 'newkey.txt';
        $this->make_it_fail_on_copy($key, $sourceKey);
        $this->copy($sourceKey, $key)->shouldBe(false);
    }

    public function it_should_create_directories()
    {
        $config = new Config();
        $path = 'dir/name';
        $this->client->createObjectDir(
            $this->bucket,
            self::PATH_PREFIX . '/' . $path,
            Argument::type('array')
        )->willReturn([
            'body' => 'body',
        ]);

        $this->createDir($path, $config)->shouldBeArray();
    }

    public function it_should_create_directories_return_false()
    {
        $config = new Config();
        $path = 'dir/name';
        $this->client->createObjectDir(
            $this->bucket,
            self::PATH_PREFIX . '/' . $path,
            Argument::type('array')
        )->willThrow(returnOssException('RequestTimeout'));

        $this->createDir($path, $config)->shouldBe(false);
    }

    public function it_should_return_false_during_rename_when_copy_fails()
    {
        $sourceKey = 'key.txt';
        $key = 'newkey.txt';
        $this->make_it_fail_on_copy($key, $sourceKey);
        $this->make_it_retrieve_raw_visibility($sourceKey, 'private');
        $this->rename($sourceKey, $key)->shouldBe(false);
    }

    public function it_should_copy_and_delete_during_renames()
    {
        $sourceKey = 'key.txt';
        $key = 'newkey.txt';

        $this->make_it_copy_successfully($key, $sourceKey);
        $this->make_it_delete_successfully($sourceKey);
        $this->make_it_404_on_has_object($sourceKey);

        $this->rename($sourceKey, $key)->shouldBe(true);
    }

    public function it_should_catch_404s_when_fetching_metadata()
    {
        $key = 'haha.txt';
        $this->make_it_404_on_get_metadata($key);

        $this->getMetadata($key)->shouldBe(false);
    }

    public function it_should_rethrow_non_404_responses_when_fetching_metadata()
    {
        $key = 'haha.txt';
        $exception = returnOssException('', 500);
        $this->client->getObjectMeta($this->bucket, self::PATH_PREFIX . '/' . $key)
            ->willThrow($exception);

        $this->shouldThrow($exception)->duringGetMetadata($key);
    }

    public function it_should_delete_directories()
    {
        $this->deleteDir('prefix')->shouldBe(false);
    }

    public function it_should_get_the_visibility_of_a_public_file()
    {
        $key = 'key.txt';
        $this->make_it_retrieve_raw_visibility($key, 'public-read');
        $this->getVisibility($key)->shouldHaveKey('visibility');
        $this->getVisibility($key)->shouldHaveValue('public-read');
    }

    public function it_should_get_the_visibility_of_a_private_file()
    {
        $key = 'key.txt';
        $this->make_it_retrieve_raw_visibility($key, 'private');
        $this->getVisibility($key)->shouldHaveKey('visibility');
        $this->getVisibility($key)->shouldHaveValue('private');
    }

    public function it_should_get_the_visibility_throw_exception()
    {
        $key = 'key.txt';
        $this->make_it_retrieve_raw_visibility_return_false($key);
        $this->getVisibility($key)->shouldBe(false);
    }

    public function it_should_set_the_visibility_of_a_file_to_public()
    {
        $key = 'key.txt';
        $this->client->putObjectAcl($this->bucket, self::PATH_PREFIX . '/' . $key, 'public-read')
            ->shouldBeCalled();

        $this->setVisibility($key, 'public-read')->shouldHaveValue('public-read');
    }

    public function it_should_set_the_visibility_of_a_file_to_private()
    {
        $key = 'key.txt';
        $this->client->putObjectAcl($this->bucket, self::PATH_PREFIX . '/' . $key, 'private')
            ->shouldBeCalled();

        $this->setVisibility($key, 'private')->shouldHaveValue('private');
    }

    public function it_should_return_false_when_failing_to_set_visibility()
    {
        $key = 'key.txt';
        $this->client->putObjectAcl($this->bucket, self::PATH_PREFIX . '/' . $key, 'private')
            ->willThrow(returnOssException('RequestTimeout'));

        $this->setVisibility($key, 'private')->shouldBe(false);
    }

    private function make_it_write_using($method, $body)
    {
        $config = new Config();
        $key = 'key.txt';
        $this->client->putObject(
            $this->bucket,
            self::PATH_PREFIX . '/' . $key,
            $body,
            Argument::type('array')
        )->willReturn(['body' => '']);

        $this->{$method}($key, $body, $config)->shouldBeArray();
    }

    private function make_it_copy_successfully($key, $sourceKey)
    {
        $this->client->copyObject(
            $this->bucket,
            self::PATH_PREFIX . '/' . $sourceKey,
            $this->bucket,
            self::PATH_PREFIX . '/' . $key,
            Argument::type('array')
        )->willReturn(true);
    }

    private function make_it_delete_successfully($sourceKey)
    {
        $this->client->deleteObject(
            $this->bucket,
            self::PATH_PREFIX . '/' . $sourceKey,
            Argument::type('array'))->shouldBeCalled();
    }

    private function make_it_fail_on_copy($key, $sourceKey)
    {
        $this->client->copyObject(
            $this->bucket,
            self::PATH_PREFIX . '/' . $sourceKey,
            $this->bucket,
            self::PATH_PREFIX . '/' . $key,
            Argument::type('array')
        )->willThrow(returnOssException('AccessDenied', 403));
    }

    private function make_it_retrieve_raw_visibility($key, $visibility)
    {
        $this->client->getObjectAcl($this->bucket, self::PATH_PREFIX . '/' . $key)
            ->willReturn($visibility);
    }

    private function make_it_retrieve_raw_visibility_return_false($key)
    {
        $this->client->getObjectAcl($this->bucket, self::PATH_PREFIX . '/' . $key)
            ->willThrow(returnOssException('RequestTimeout'));
    }

    private function make_it_retrieve_file_metadata($method)
    {
        $key = 'key.txt';

        $this->client->getObjectMeta($this->bucket, self::PATH_PREFIX . '/' . $key)
            ->willReturn([
                'oss-request-id'     => 'xxxxx',
                'oss-request-url'    => 'xxxxx',
                'oss-redirects'      => 'xxxxx',
                'oss-stringtosign'   => 'xxxxx',
                'oss-requestheaders' => 'xxxxx',
                'content-length'     => 12789,
                'last-modified'      => 'Fri, 24 Feb 2012 06:07:48 GMT',
                'date'               => 'Wed, 29 Apr 2015 05:21:12 GMT',
                'eTag'               => '5B3C1A2E053D763E1B002CC607C5A0FE',
                'connection'         => 'keep-alive',
                'server'             => 'aliyunoss',
                'info'              => [
                    'content_type' => 'text/plain',
                ],
            ]);

        $this->{$method}($key)->shouldBeArray();
    }

    private function make_it_retrieve_file_metadata_return_false($method)
    {
        $key = 'key.txt';

        $this->client->getObjectMeta($this->bucket, self::PATH_PREFIX . '/' . $key)
            ->willThrow(returnOssException('NoSuchKey', 404));

        $this->{$method}($key)->shouldBe(false);
    }

    private function make_it_read_a_file($method, $contents)
    {
        $key = 'key.txt';
        $returnContents = 'contents string';
        if (is_scalar($contents)) {
            $this->client->getObject($this->bucket, self::PATH_PREFIX . '/' . $key)
                ->willReturn($returnContents);
        } else {
            $this->client->signUrl($this->bucket, self::PATH_PREFIX . '/' . $key, 60, OssClient::OSS_HTTP_GET, Argument::type('array'))
                ->willReturn('http://www.baidu.com');
        }

        $this->{$method}($key)->shouldBeArray();
    }

    private function make_it_read_a_file_return_false($method, $contents)
    {
        $key = 'key.txt';
        if (is_scalar($contents)) {
            $this->client->getObject($this->bucket, self::PATH_PREFIX . '/' . $key)
                ->willThrow(returnOssException('NoSuchKey', 404));
        } else {
            $this->client->signUrl($this->bucket, self::PATH_PREFIX . '/' . $key, 60, OssClient::OSS_HTTP_GET, Argument::type('array'))
                ->willThrow(returnOssException('NoSuchKey', 404));
        }

        $this->{$method}($key)->shouldBe(false);
    }

    public function getMatchers()
    {
        return [
            'haveKey'   => function ($subject, $key) {
                return array_key_exists($key, $subject);
            },
            'haveValue' => function ($subject, $value) {
                return in_array($value, $subject);
            },
        ];
    }

    private function make_it_404_on_has_object($key)
    {
        $this->client->doesObjectExist($this->bucket, self::PATH_PREFIX . '/' . $key, Argument::type('array'))
            ->willReturn(false);

        $result = new ObjectListInfo($this->bucket,
            self::PATH_PREFIX . '/',
            '',
            OssUtil::decodeKey('', ''),
            1,
            '/',
            '',
            [],
            []);
        $this->client->listObjects($this->bucket, [
            OssClient::OSS_PREFIX   => self::PATH_PREFIX . '/' . $key . '/',
            OssClient::OSS_MAX_KEYS => 1,
        ])->willReturn($result);
    }
    
    private function make_it_404_on_get_metadata($key)
    {
        $this->client->getObjectMeta($this->bucket, self::PATH_PREFIX . '/' . $key)
            ->willThrow(returnOssException('', 404));
    }
}


function returnOssException($code, $httpStatus = 200, $message = '', $requestId = '')
{
    return new OssException([
        'status'     => $httpStatus,
        'code'       => $code,
        'message'    => $message,
        'request-id' => $requestId,
    ]);
}