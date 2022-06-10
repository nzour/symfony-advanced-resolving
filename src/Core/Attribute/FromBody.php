<?php

declare(strict_types=1);

namespace AdvancedResolving\Core\Attribute;

use Attribute;
use JetBrains\PhpStorm\ExpectedValues;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\YamlEncoder;

/**
 * @psalm-type Format = JsonEncoder::FORMAT | XmlEncoder::FORMAT | YamlEncoder::FORMAT | CsvEncoder::FORMAT
 *
 * @psalm-immutable
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final class FromBody
{
    public const DEFAULT_FORMAT = JsonEncoder::FORMAT;

    /**
     * @psalm-suppress UndefinedAttributeClass
     */
    public function __construct(
        #[ExpectedValues([
            JsonEncoder::FORMAT,
            XmlEncoder::FORMAT,
            YamlEncoder::FORMAT,
            CsvEncoder::FORMAT,
        ])]
        public string $format = self::DEFAULT_FORMAT,
    ) {
    }
}
