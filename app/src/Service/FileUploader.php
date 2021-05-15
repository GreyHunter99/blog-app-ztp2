<?php
/**
 * File uploader.
 */

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class FileUploader.
 */
class FileUploader
{
    /**
     * Target directory.
     *
     * @var string
     */
    private $targetDirectory;

    /**
     * FileUploader constructor.
     *
     * @param string $targetDirectory Target director
     */
    public function __construct(string $targetDirectory)
    {
        $this->targetDirectory = $targetDirectory;
    }

    /**
     * Upload file.
     *
     * @param UploadedFile $file File to upload
     *
     * @return string Filename of uploaded file
     */
    public function upload(UploadedFile $file): string
    {
        $fileName = $this->generateFilename(
            pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            $file->guessClientExtension()
        );

        try {
            $file->move($this->getTargetDirectory(), $fileName);
        } catch (FileException $exception) {
            // ... handle exception if something happens during file upload
        }

        return $fileName;
    }

    /**
     * Getter for target directory.
     *
     * @return string Target directory
     */
    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }

    /**
     * Generates new filename.
     *
     * @param string $originalFilename Original filename
     * @param string $extension        File extension
     *
     * @return string New filename
     */
    private function generateFilename(string $originalFilename, string $extension): string
    {
        $safeFilename = transliterator_transliterate(
            'Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()',
            $originalFilename
        );

        return $safeFilename.'-'.uniqid().'.'.$extension;
    }
}