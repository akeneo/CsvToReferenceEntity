<?php

namespace App\Processor\Converter;

class NumberConverter implements DataConverterInterface
{
    public function support(array $attribute): bool
    {
        return isset($attribute['type']) && 'number' === $attribute['type'];
    }

    public function convert(array $attribute, string $data, array $context)
    {
        return $data;
    }
}
