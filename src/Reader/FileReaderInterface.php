<?php

declare(strict_types=1);

namespace App\Reader;

/**
 * @author    Adrien Pétremann <adrien.petremann@akeneo.com>
 * @copyright 2019 Akeneo SAS (https://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
interface FileReaderInterface extends \Iterator
{
    /**
     * Returns file headers
     */
    public function getHeaders(): array;

    /**
     * Return number of lines
     */
    public function count(): int;
}
