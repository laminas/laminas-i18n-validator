<?php

declare(strict_types=1);

namespace LaminasTest\I18n\Validator;

use Stringable;

final readonly class StringableObject implements Stringable
{
    public function __construct(private string $string)
    {
    }

    public function __toString(): string
    {
        return $this->string;
    }
}
