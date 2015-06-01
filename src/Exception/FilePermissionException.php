<?php

namespace Dotenv\Exception;

class FilePermissionException extends AbstractFileException
{
    /**
     * {@inheritDoc}
     */
    protected function generateMessage()
    {
        $filePath = $this->filePath;
        $fileName = basename($filePath);
        return sprintf(
            'Dotenv: Environment file %s not readable. '.
            'Ensures the given filePath %s is readable',
            $fileName,
            $filePath
        );
    }
}
