# JimChen\Flysystem\AliyunOss

This is a Flysystem adapter for the Aliyun Oss SDK v2.*

## Installing

```shell
$ composer require jimchen/flysystem-aliyun-oss -vvv
```

## Usage

``` php
<?php
use OSS\OssClient;
use League\Flysystem\Filesystem;
use JimChen\Flysystem\AliyunOss\AliyunOssAdapter;

include __DIR__ . '/vendor/autoload.php';

$client = new OssClient([
    'your-access-key-id',
    'your-access-key-secret',
    'your-bucket',
    'your-endpoint'
]);

$adapter = new AliyunOssAdapter($client, 'your-bucket-name');
$filesystem = new Filesystem($adapter);
```

## License

MIT