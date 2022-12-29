<?php

declare(strict_types=1);

namespace CannaPress\Util\Logging;

use Psr\Log\LogLevel;

final class Level
{

    public const Debug = 100;
    public const Info = 200;
    public const Notice = 250;
    public const Warning = 300;
    public const Error = 400;
    public const Critical = 500;
    public const Alert = 550;
    public const Emergency = 600;

    /**
     * Returns the PSR-3 level matching this instance
     *
     * @phpstan-return \Psr\Log\LogLevel::*
     */
    public static function getName(int $level): string
    {
        if ($level === self::Debug) {
            return LogLevel::DEBUG;
        }
        if ($level === self::Info) {
            return LogLevel::INFO;
        }
        if ($level === self::Notice) {
            return LogLevel::NOTICE;
        }
        if ($level === self::Warning) {
            return LogLevel::WARNING;
        }
        if ($level === self::Error) {
            return LogLevel::ERROR;
        }
        if ($level === self::Critical) {
            return LogLevel::CRITICAL;
        }
        if ($level === self::Alert) {
            return LogLevel::ALERT;
        }
        if ($level === self::Emergency) {
            return LogLevel::EMERGENCY;
        }
        return LogLevel::ERROR;
    }


    public static function fromName(string $name): int
    {

        if (in_array($name, ['debug', 'Debug', 'DEBUG'])) {
            return self::Debug;
        }
        if (in_array($name, ['info', 'Info', 'INFO'])) {
            return self::Info;
        }
        if (in_array($name, ['notice', 'Notice', 'NOTICE'])) {
            return self::Notice;
        }
        if (in_array($name, ['warning', 'Warning', 'WARNING'])) {
            return self::Warning;
        }
        if (in_array($name, ['error', 'Error', 'ERROR'])) {
            return self::Error;
        }
        if (in_array($name, ['critical', 'Critical', 'CRITICAL'])) {
            return self::Critical;
        }
        if (in_array($name, ['alert', 'Alert', 'ALERT'])) {
            return self::Alert;
        }
        if (in_array($name, ['emergency', 'Emergency', 'EMERGENCY'])) {
            return self::Emergency;
        }
        return self::Error;
    }

    public static function normalize(string|int $level): int
    {
        return is_int($level) ? $level : self::fromName($level);
    }
    public static function compare(string|int $a, string|int $b): int
    {
        return self::normalize($a) - self::normalize($b);
    }

    public const VALUES = [
        100,
        200,
        250,
        300,
        400,
        500,
        550,
        600,
    ];

    public const NAMES = [
        'DEBUG',
        'INFO',
        'NOTICE',
        'WARNING',
        'ERROR',
        'CRITICAL',
        'ALERT',
        'EMERGENCY',
    ];
}
