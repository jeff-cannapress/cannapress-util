<?php

declare(strict_types=1);

namespace CannaPress\Util\Logging;

class Record
{
    public function __construct(
        public \DateTimeImmutable $datetime,
        public string $channel,
        public int $level,
        public string $message,
        /** @var array<mixed> */
        public array $context = [],
        /** @var array<mixed> */
        public array $extra = [],
    ) {
    }
}
