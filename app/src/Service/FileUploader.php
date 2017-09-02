<?php
/**
 * File Uploader service.
 */
namespace Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class FileUploader.
 *
 * @package Service
 */
class FileUploader
{
    /**
     * Target directory.
     *
     * @var string $targetDir
     */
    protected $targetDir;

    /**
     * FileUploader constructor.
     *
     * @param string $targetDir Target directory
     */
    public function __construct($targetDir)
    {
        $this->targetDir = $targetDir;
    }

    /**
     * Upload file.
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file
     *
     * @return string File name
     */
    public function upload(UploadedFile $file)
    {
        $fileName = md5(uniqid()).'.'.$file->guessExtension();
        $file->move($this->targetDir, $fileName);

        return $fileName;
    }

    /**
     * Get target directory.
     *
     * @return string Target directory
     */
    public function getTargetDir()
    {
        return $this->targetDir;
    }
}