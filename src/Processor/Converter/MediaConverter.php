<?php
/**
 * Diglin GmbH - Switzerland
 *
 * @author      Sylvain Rayé <support at diglin.com>
 * @category    Diglin
 * @package     csv_to_reference_entity
 * @copyright   Copyright (c) Diglin (https://www.diglin.com)
 */

declare(strict_types=1);

namespace App\Processor\Converter;

class MediaConverter implements DataConverterInterface
{
    /**
     * Does this data converter support the given $attribute
     *
     * @param array $attribute
     *
     * @return bool
     */
    public function support(array $attribute): bool
    {
        return isset($attribute['type']) && 'image' === $attribute['type'];
    }

    /**
     * Convert the given $data for the given $attribute to the correct format expected by the Akeneo API
     *
     * @param array $attribute
     * @param string $data
     *
     * @return string|null
     */
    public function convert(array $attribute, string $data): ?string
    {
        if (empty($data)) {
            return null;
        }

        $jsonArray = json_decode($data, true);

        return (isset($jsonArray['filePath']) ? $jsonArray['filePath'] : null);
    }
}