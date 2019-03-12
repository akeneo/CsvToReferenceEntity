<?php

declare(strict_types=1);

namespace App\Processor;

use App\Processor\Converter\DataConverter;

/**
 * Helper to generate the structure of the value collection (all possible value keys).
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2019 Akeneo SAS (https://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class StructureGenerator
{
    /** @var ValueKeyGenerator */
    private $valueKeyGenerator;

    /** @var DataConverter */
    private $converter;

    public function __construct(ValueKeyGenerator $valueKeyGenerator, DataConverter $converter)
    {
        $this->valueKeyGenerator = $valueKeyGenerator;
        $this->converter = $converter;
    }

    /**
     * For the given $attributes and $channels, generates all possible value keys as an array of string
     *
     * eg.: [
     *  'description-fr_FR-mobile',
     *  'description-en_US-mobile',
     *  'name',
     *  'weight',
     * ]
     */
    public function generate(array $attributes, array $channels): array
    {
        $valueKeys = [];
        foreach ($attributes as $attribute) {
            $valueKeys = array_merge($valueKeys, $this->valueKeyGenerator->generate($attribute, $channels));
        }

        return $valueKeys;
    }
}
