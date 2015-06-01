<?php

namespace Dotenv\Exception;

class FileNotFoundException extends AbstractFileException
{
    /**
     * {@inheritDoc}
     */
    protected function generateMessage()
    {
        $filePath = $this->filePath;
        $fileName = basename($filePath);
        return sprintf(
            'Dotenv: Environment file %s not found. '.
            'Create file with your environment settings at %s',
            $fileName,
            $filePath
        );
    }
}
