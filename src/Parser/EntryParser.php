<?php

declare(strict_types=1);

namespace Dotenv\Parser;

use Dotenv\Util\Regex;
use Dotenv\Util\Str;
use GrahamCampbell\ResultType\Error;
use GrahamCampbell\ResultType\Result;
use GrahamCampbell\ResultType\Success;
use PhpOption\None;
use PhpOption\Some;

final class EntryParser
{
    private const INITIAL_STATE = 0;
    private const UNQUOTED_STATE = 1;
    private const SINGLE_QUOTED_STATE = 2;
    private const DOUBLE_QUOTED_STATE = 3;
    private const ESCAPE_SEQUENCE_STATE = 4;
    private const WHITESPACE_STATE = 5;
    private const COMMENT_STATE = 6;
    private const REJECT_STATES = [self::SINGLE_QUOTED_STATE, self::DOUBLE_QUOTED_STATE, self::ESCAPE_SEQUENCE_STATE];

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
     * Parse a raw entry into a proper entry.
     *
     * That is, turn a raw environment variable entry into a name and possibly
     * a value. We wrap the answer in a result type.
     *
     * @param string $entry
     *
     * @return \GrahamCampbell\ResultType\Result<\Dotenv\Parser\Entry, string>
     */
    public static function parse(string $entry)
    {
        return self::splitStringIntoParts($entry)->flatMap(static function (array $parts) {
            [$name, $value] = $parts;

            return self::parseName($name)->flatMap(static function (string $name) use ($value) {
                $parsedValue = $value === null ? Success::create(null) : self::parseValue($value);

                return $parsedValue->map(static function (?Value $value) use ($name) {
                    return new Entry($name, $value);
                });
            });
        });
    }

    /**
     * Split the compound string into parts.
     *
     * @param string $line
     *
     * @return \GrahamCampbell\ResultType\Result<array{string, string|null},string>
     */
    private static function splitStringIntoParts(string $line)
    {
        $result = \explode('=', $line, 2);

        if (isset($result[1])) {
            $result = [\trim($result[0], " \n\r\t\0\x0B"), \trim($result[1], " \n\r\t\0\x0B")];
        } else {
            $result = [$line, null];
        }

        if ($result[0] === '') {
            return Error::create(self::getErrorMessage('an unexpected equals', $line));
        }

        return Success::create($result);
    }

    /**
     * Parse the given variable name.
     *
     * That is, strip the optional quotes and leading "export" from the
     * variable name. We wrap the answer in a result type.
     *
     * @param string $name
     *
     * @return \GrahamCampbell\ResultType\Result<string, string>
     */
    private static function parseName(string $name)
    {
        if (\strlen($name) > 6 && \substr($name, 0, 6) === 'export' && \ctype_space($name[6])) {
            $name = \ltrim(\substr($name, 6), " \t\n\r\v\f");
        }

        if (self::isQuotedName($name)) {
            $name = \substr($name, 1, -1);
        }

        if (!self::isValidName($name)) {
            return Error::create(self::getErrorMessage('an invalid name', $name));
        }

        return Success::create($name);
    }

    /**
     * Is the given variable name quoted?
     *
     * @param string $name
     *
     * @return bool
     */
    private static function isQuotedName(string $name)
    {
        if (\strlen($name) < 3) {
            return false;
        }

        $first = $name[0];
        $last = $name[\strlen($name) - 1];

        return ($first === '"' && $last === '"') || ($first === '\'' && $last === '\'');
    }

    /**
     * Is the given variable name valid?
     *
     * @param string $name
     *
     * @return bool
     */
    private static function isValidName(string $name)
    {
        if (\preg_match('~\A[a-zA-Z0-9_.]+\z~', $name) === 1) {
            return true;
        }

        return Regex::matches('~\A[\p{Ll}\p{Lu}\p{M}\p{N}_.]+\z~u', $name)->success()->getOrElse(false);
    }

    /**
     * Parse the given variable value.
     *
     * This has the effect of stripping quotes and comments, dealing with
     * special characters, and locating nested variables, but not resolving
     * them. Formally, we run a finite state automaton with an output tape: a
     * transducer. We wrap the answer in a result type.
     *
     * @param string $value
     *
     * @return \GrahamCampbell\ResultType\Result<\Dotenv\Parser\Value, string>
     */
    private static function parseValue(string $value)
    {
        if (\trim($value, " \n\r\t\0\x0B") === '') {
            return Success::create(Value::blank());
        }

        $literal = self::parseLiteral($value);

        if ($literal->isDefined()) {
            return Success::create(Value::blank()->append($literal->get(), false));
        }

        $chars = '';
        $pending = '';
        $length = 0;
        $vars = [];
        $state = self::INITIAL_STATE;

        foreach (Lexer::lex($value) as $token) {
            $result = self::processToken($state, $token);

            if ($result->error()->isDefined()) {
                return Error::create(self::getErrorMessage($result->error()->get(), $value));
            }

            [$chunk, $var, $state] = $result->success()->get();

            if ($var) {
                $chars .= $pending;
                $length += Str::len($pending);
                $pending = '';
                $vars[] = $length;
            }

            $pending .= $chunk;
        }

        if (\in_array($state, self::REJECT_STATES, true)) {
            return Error::create(self::getErrorMessage('a missing closing quote', $value));
        }

        return Success::create(Value::create($chars.$pending, $vars));
    }

    /**
     * Parse the given variable value, if it is a literal.
     *
     * That is, a value which can be used verbatim, save stripping quotes.
     * Unquoted literals are capped at the lexer's chunk size, so that the
     * fast path agrees with the transducer under any libc locale.
     *
     * @param string $value
     *
     * @return \PhpOption\Option<string>
     */
    private static function parseLiteral(string $value)
    {
        if (\preg_match('~\A(?|([^\s\\\\\'"#$]{1,1000})|\'([^\']*)\'|"([^"\\\\$]*)")\z~', $value, $matches) === 1) {
            return Some::create($matches[1]);
        }

        return None::create();
    }

    /**
     * Process the given token.
     *
     * @param int    $state
     * @param string $token
     *
     * @return \GrahamCampbell\ResultType\Result<array{string, bool, int}, string>
     */
    private static function processToken(int $state, string $token)
    {
        switch ($state) {
            case self::INITIAL_STATE:
                if ($token === '\'') {
                    return Success::create(['', false, self::SINGLE_QUOTED_STATE]);
                } elseif ($token === '"') {
                    return Success::create(['', false, self::DOUBLE_QUOTED_STATE]);
                } elseif ($token === '#') {
                    return Success::create(['', false, self::COMMENT_STATE]);
                } elseif ($token === '$') {
                    return Success::create([$token, true, self::UNQUOTED_STATE]);
                } else {
                    return Success::create([$token, false, self::UNQUOTED_STATE]);
                }
            case self::UNQUOTED_STATE:
                if ($token === '#') {
                    return Success::create(['', false, self::COMMENT_STATE]);
                } elseif (\ctype_space($token)) {
                    return Success::create(['', false, self::WHITESPACE_STATE]);
                } elseif ($token === '$') {
                    return Success::create([$token, true, self::UNQUOTED_STATE]);
                } else {
                    return Success::create([$token, false, self::UNQUOTED_STATE]);
                }
            case self::SINGLE_QUOTED_STATE:
                if ($token === '\'') {
                    return Success::create(['', false, self::WHITESPACE_STATE]);
                } else {
                    return Success::create([$token, false, self::SINGLE_QUOTED_STATE]);
                }
            case self::DOUBLE_QUOTED_STATE:
                if ($token === '"') {
                    return Success::create(['', false, self::WHITESPACE_STATE]);
                } elseif ($token === '\\') {
                    return Success::create(['', false, self::ESCAPE_SEQUENCE_STATE]);
                } elseif ($token === '$') {
                    return Success::create([$token, true, self::DOUBLE_QUOTED_STATE]);
                } else {
                    return Success::create([$token, false, self::DOUBLE_QUOTED_STATE]);
                }
            case self::ESCAPE_SEQUENCE_STATE:
                if ($token === '"' || $token === '\\') {
                    return Success::create([$token, false, self::DOUBLE_QUOTED_STATE]);
                } elseif ($token === '$') {
                    return Success::create([$token, false, self::DOUBLE_QUOTED_STATE]);
                } else {
                    $first = Str::substr($token, 0, 1);
                    if (\in_array($first, ['f', 'n', 'r', 't', 'v'], true)) {
                        return Success::create([\stripcslashes('\\'.$first).Str::substr($token, 1), false, self::DOUBLE_QUOTED_STATE]);
                    } else {
                        return Error::create('an unexpected escape sequence');
                    }
                }
            case self::WHITESPACE_STATE:
                if ($token === '#') {
                    return Success::create(['', false, self::COMMENT_STATE]);
                } elseif (!\ctype_space($token)) {
                    return Error::create('unexpected whitespace');
                } else {
                    return Success::create(['', false, self::WHITESPACE_STATE]);
                }
            case self::COMMENT_STATE:
                return Success::create(['', false, self::COMMENT_STATE]);
            default:
                throw new \Error('Parser entered invalid state.');
        }
    }

    /**
     * Generate a friendly error message.
     *
     * @param string $cause
     * @param string $subject
     *
     * @return string
     */
    private static function getErrorMessage(string $cause, string $subject)
    {
        $line = \strtok($subject, "\n");

        if ($line === false) {
            $line = '';
        }

        if (\strlen($line) > 80) {
            $line = self::cutBytes($line, 80).'...';
        }

        $line = \addcslashes($line, "\0..\37\177");

        return \sprintf(
            'Encountered %s at [%s].',
            $cause,
            $line
        );
    }

    /**
     * Cut the string to at most the given number of bytes.
     *
     * The cut never splits a multibyte UTF-8 character: an incomplete
     * trailing sequence is dropped entirely.
     *
     * @param string $input
     * @param int    $limit
     *
     * @return string
     */
    private static function cutBytes(string $input, int $limit)
    {
        $prefix = \substr($input, 0, $limit);
        $length = \strlen($prefix);

        for ($i = $length - 1; $i >= 0 && $i >= $length - 4; $i--) {
            $byte = \ord($prefix[$i]);

            if (($byte & 0xC0) !== 0x80) {
                $need = $byte < 0x80 ? 1 : ($byte >= 0xF0 ? 4 : ($byte >= 0xE0 ? 3 : ($byte >= 0xC0 ? 2 : 1)));

                if ($i + $need > $length) {
                    $prefix = \substr($prefix, 0, $i);
                }

                break;
            }
        }

        return $prefix;
    }
}
