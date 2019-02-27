<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\Yaml\Yaml;

/**
 * Simple wrapper of the Yaml reader component of Symfony
 *
 * @author    Adrien Pétremann <adrien.petremann@akeneo.com>
 * @copyright 2019 Akeneo SAS (https://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class YamlReader
{
    public function parseFile(string $filename, int $flags = 0)
    {
        return Yaml::parseFile($filename, $flags);
    }
}
