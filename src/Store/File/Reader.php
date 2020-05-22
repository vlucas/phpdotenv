<?php

declare(strict_types=1);

namespace Dotenv\Store\File;

use Dotenv\Exception\InvalidEncodingException;
use GrahamCampbell\ResultType\Error;
use GrahamCampbell\ResultType\Success;
use PhpOption\Option;

/**
 * @internal
 */
final class Reader
{
    /**
     * This class is a singleton.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    private function __construct()
    {
        //
    }

    /**
     * Read the file(s), and return their raw content.
     *
     * We provide the file path as the key, and its content as the value. If
     * short circuit mode is enabled, then the returned array with have length
     * at most one. File paths that couldn't be read are omitted entirely.
     *
     * @param string[]    $filePaths
     * @param bool        $shortCircuit
     * @param string|null $fileEncoding
     *
     * @throws \Dotenv\Exception\InvalidEncodingException
     *
     * @return array<string,string>
     */
    public static function read(array $filePaths, bool $shortCircuit = true, string $fileEncoding = null)
    {
        $output = [];

        foreach ($filePaths as $filePath) {
            $content = self::readFromFile($filePath, $fileEncoding);
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
     * @param string      $path
     * @param string|null $encoding
     *
     * @throws \Dotenv\Exception\InvalidEncodingException
     *
     * @return \PhpOption\Option<string>
     */
    private static function readFromFile(string $path, string $encoding = null)
    {
        if ($encoding !== null && !in_array($encoding, mb_list_encodings(), true)) {
            throw new InvalidEncodingException(
                sprintf('Illegal character encoding [%s] specified.', $encoding)
            );
        }

        return Option::fromValue(@file_get_contents($path), false)->map(function (string $content) use ($encoding) {
            return $encoding === null ? @mb_convert_encoding($content, 'UTF-8') : @mb_convert_encoding($content, 'UTF-8', $encoding);
        });
    }
}
