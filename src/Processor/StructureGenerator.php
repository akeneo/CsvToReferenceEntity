<?php

declare(strict_types=1);

namespace App\Processor;

use App\Processor\Converter\DataConverter;

/**
 * Helper to generate the structure of the value collection
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

    public function __construct(ValueKeyGenerator $valueKeyGenerator, DataConverter $converter) {
        $this->valueKeyGenerator = $valueKeyGenerator;
        $this->converter = $converter;
    }

    public function generate(array $attributes, array $headers, array $channels) {
      return array_reduce($attributes, function (array $indexedValueKeys, array $attribute) use ($headers, $channels) {
          if ($this->converter->support($attribute)) {
              $attributeSupportedValueKeys = $this->valueKeyGenerator->generate($attribute, $channels);
              $attributeValueKeysToProcess = array_intersect($headers, $attributeSupportedValueKeys);

              if (!empty($attributeValueKeysToProcess)) {
                  $indexedValueKeys[$attribute['code']] = $attributeValueKeysToProcess;
              }
          }

          return $indexedValueKeys;
      }, []);
    }
}
