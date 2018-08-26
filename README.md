# JimChen\Flysystem\AliyunOss

[![Author](http://img.shields.io/badge/author-@JimChen-blue.svg?style=flat-square)](https://github.com/JimChenWYU)
[![Build Status](https://img.shields.io/travis/JimChenWYU/flysystem-aliyun-oss.svg?style=flat-square)](https://www.travis-ci.org/JimChenWYU/flysystem-aliyun-oss)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/JimChenWYU/flysystem-aliyun-oss.svg?style=flat-square)](https://scrutinizer-ci.com/g/JimChenWYU/flysystem-aliyun-oss/)
[![Quality Score](https://img.shields.io/scrutinizer/g/JimChenWYU/flysystem-aliyun-oss.svg?style=flat-square)](https://scrutinizer-ci.com/g/JimChenWYU/flysystem-aliyun-oss/)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/jimchen/flysystem-aliyun-oss.svg?style=flat-square)](https://packagist.org/packages/jimchen/flysystem-aliyun-oss)

This is a Flysystem adapter for the Aliyun Oss SDK v2.*

## Requirement

- PHP >= 5.5.9
- Composer
- Openssl Extension
- cURL Extension

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
    'your-bucket-name',
    'your-endpoint'
]);

$adapter = new AliyunOssAdapter($client, 'your-bucket-name');
$filesystem = new Filesystem($adapter);
```

## License

MIT