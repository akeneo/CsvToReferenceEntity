<?php

declare(strict_types=1);

namespace App\Processor\Converter;

/**
 * Prepares data to be sent through the API
 *
 * @author    Adrien Pétremann <adrien.petremann@akeneo.com>
 * @copyright 2019 Akeneo SAS (https://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
interface DataConverterInterface
{
    /**
     * Does this data converter support the given $attribute
     */
    public function support(array $attribute): bool;

    /**
     * Convert the given $data for the given $attribute to the correct format expected by the Akeneo API
     */
    public function convert(array $attribute, string $data, array $context);
}
