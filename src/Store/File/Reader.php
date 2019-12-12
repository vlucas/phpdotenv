<?php

namespace Dotenv\Store\File;

use PhpOption\Option;

class Reader
{
    /**
     * Read the file(s), and return their raw content.
     *
     * We provide the file path as the key, and its content as the value. If
     * short circuit mode is enabled, then the returned array with have length
     * at most one. File paths that couldn't be read are omitted entirely.
     *
     * @param string[] $filePaths
     * @param bool     $shortCircuit
     *
     * @return array<string,string>
     */
    public static function read(array $filePaths, $shortCircuit = true)
    {
        $output = [];

        foreach ($filePaths as $filePath) {
            $content = self::readFromFile($filePath);
            if ($content->isDefined()) {
                $output[$filePath] = $content->get();
                if ($shortCircuit) {
                    break;
                }
            }
        }

        return $output;
    }

    /**
     * Read the given file.
     *
     * @param string $filePath
     *
     * @return \PhpOption\Option<string>
     */
    private static function readFromFile($filePath)
    {
        $content = @file_get_contents($filePath);

        return Option::fromValue($content, false);
    }
}
