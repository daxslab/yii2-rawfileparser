<?php

namespace daxslab\rawfileparser;

use yii\helpers\FileHelper;
use yii\helpers\StringHelper;
use yii\web\RequestParserInterface;

class RawFileParser implements RequestParserInterface
{

    private $_uploadFileMaxSize;
    public $baseName = null;

    public function getUploadFileMaxSize()
    {
        if ($this->_uploadFileMaxSize === null) {
            $this->_uploadFileMaxSize = $this->getByteSize(ini_get('upload_max_filesize'));
        }

        return $this->_uploadFileMaxSize;
    }

    /**
     * @param int $uploadFileMaxSize upload file max size in bytes.
     */
    public function setUploadFileMaxSize(int $uploadFileMaxSize): void
    {
        $this->_uploadFileMaxSize = $uploadFileMaxSize;
    }

    /**
     * @inheritDoc
     */
    public function parse($rawBody, $contentType): array
    {

        if ($this->baseName === null) {
            $this->baseName = md5($rawBody);
        }

        $extension = current(FileHelper::getExtensionsByMimeType($contentType));

        $fileInfo = [
            'name' => $this->baseName . ($extension ? ".$extension" : ''),
            'type' => $contentType,
            'size' => StringHelper::byteLength($rawBody),
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => null,
        ];

        if ($fileInfo['size'] === 0 || $fileInfo['size'] > $this->getUploadFileMaxSize()) {
            $fileInfo['error'] = UPLOAD_ERR_INI_SIZE;
        } else {
            $tmpResource = tmpfile();
            if ($tmpResource === false) {
                $fileInfo['error'] = UPLOAD_ERR_CANT_WRITE;
            } else {
                $tmpResourceMetaData = stream_get_meta_data($tmpResource);
                $tmpFileName = $tmpResourceMetaData['uri'];
                if (empty($tmpFileName)) {
                    $fileInfo['error'] = UPLOAD_ERR_CANT_WRITE;
                    @fclose($tmpResource);
                } else {
                    fwrite($tmpResource, $rawBody);
                    rewind($tmpResource);
                    $fileInfo['tmp_name'] = $tmpFileName;
                    $fileInfo['tmp_resource'] = $tmpResource; // save file resource, otherwise it will be deleted
                }
            }
        }

        $_FILES[$this->baseName] = $fileInfo;

        return [];
    }

    /**
     * Gets the size in bytes from verbose size representation.
     *
     * For example: '5K' => 5*1024.
     * @param string $verboseSize verbose size representation.
     * @return int actual size in bytes.
     */
    private function getByteSize($verboseSize)
    {
        if (empty($verboseSize)) {
            return 0;
        }
        if (is_numeric($verboseSize)) {
            return (int)$verboseSize;
        }
        $sizeUnit = trim($verboseSize, '0123456789');
        $size = trim(str_replace($sizeUnit, '', $verboseSize));
        if (!is_numeric($size)) {
            return 0;
        }
        switch (strtolower($sizeUnit)) {
            case 'kb':
            case 'k':
                return $size * 1024;
            case 'mb':
            case 'm':
                return $size * 1024 * 1024;
            case 'gb':
            case 'g':
                return $size * 1024 * 1024 * 1024;
            default:
                return 0;
        }
    }
}