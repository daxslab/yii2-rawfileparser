Yii2 Raw File Parser
====================
RawFileParser is a Yii2 extension that allows you to parse the content of requests that contain a raw file based on
the `Content-Type` header. It does this by making the file available in the `$_FILES` array, allowing to handle it as a
regular file upload.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist daxslab/yii2-rawfileparser "*"
```

or add

```
"daxslab/yii2-rawfileparser": "*"
```

to the require section of your `composer.json` file.

Configuration
-------------

In order to enable this parser you should configure [[Request::parsers]] in the following way:

```php
return [
    'components' => [
        'request' => [
            'parsers' => [
                'application/zip' => [
                    'class' => 'daxslab\extensions\RawFileParser',
                    'basename' => 'azipfile' //optional but recommended, the name to locate the file in $_FILES
                ],
                'video/x-matroska' => 'daxslab\extensions\RawFileParser', //basename is not specified, the key $_FILES is a md5 hash of the file content. Ugly, yes...
            ],
        ],
        // ...
    ],
    // ...
];
```

Usage
-----

**Note:** in order to the parser to be able to work:
1. The request must have the `Content-Type` header set to to specified value in the parser configuration
2. `Yii::$app->request->getBodyParams()` or `Yii::$app->request->post()` must be called previous to any attemp to access the file because is when the parser logic is executed.

### Handling the uploaded file

```php
Yii::$app->request->getBodyParams(); //parser is executed here, the file is on $_FILES now.
$uploadedFile = UploadedFile::getInstanceByName('azipfile');

if (!$uploadedFile) {
     throw new ServerErrorHttpException(Yii::t('app', 'No file uploaded'));
}

$uploadedFile->saveAs("/path/to/save/$uploadedFile->name");
```

---
By [Daxslab](http://daxslab.com).
