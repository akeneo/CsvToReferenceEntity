<?php

declare(strict_types=1);

namespace App\Processor;

use App\Processor\Converter\DataConverter;

class RecordProcessor
{
    /** @var ValueKeyGenerator */
    private $valueKeyGenerator;

    /** @var DataConverter */
    private $dataConverter;

    public function __construct(ValueKeyGenerator $valueKeyGenerator, DataConverter $dataConverter)
    {
        $this->valueKeyGenerator = $valueKeyGenerator;
        $this->dataConverter = $dataConverter;
    }

    public function process(array $line, array $attributes, array $indexedValueKeys): array
    {
        $values = $line;
        unset($values['code']);

        $values = [];
        foreach ($attributes as $attribute) {
            $attributeCode = $attribute['code'];
            $values[$attributeCode] = [];
            $attributeValueKeys = $indexedValueKeys[$attributeCode];

            foreach ($attributeValueKeys as $attributeValueKey) {
                $context = $this->valueKeyGenerator->extract($attribute, $attributeValueKey);

                $values[$attributeCode][] = [
                    'channel' => $context['channel'],
                    'locale' => $context['locale'],
                    'data' => $this->dataConverter->convert($attribute, $values[$attributeValueKey]),
                ];
            }
        }

        return [
            'code' => $line['code'],
            'values' => $values,
        ];
    }
}
