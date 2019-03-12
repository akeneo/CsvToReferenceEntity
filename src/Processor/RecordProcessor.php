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

    public function process(array $line, array $validStructure): array
    {
        $values = [];
        foreach ($validStructure as $valueKey => $attribute) {
            $context = $this->valueKeyGenerator->extract($attribute, $valueKey);

            $values[$attribute['code']][] = [
                'channel' => $context['channel'],
                'locale' => $context['locale'],
                'data' => $this->dataConverter->convert($attribute, $line[$valueKey]),
            ];
        }

        return [
            'code' => $line['code'],
            'values' => $values,
        ];
    }
}
