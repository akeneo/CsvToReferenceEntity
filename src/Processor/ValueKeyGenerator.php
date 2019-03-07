<?php

declare(strict_types=1);

namespace App\Processor;

/**
 * Helper for "value keys", which are composed of the attribute code, a channel code and a locale code
 *
 * @author    Adrien Pétremann <adrien.petremann@akeneo.com>
 * @copyright 2019 Akeneo SAS (https://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ValueKeyGenerator
{
    /**
     * Generates all "value keys" combinations for the given $attribute on given $channels
     * and return them as an array of $attribute indexed by a value key.
     *
     * Ie.:
     * - "description-en_US-mobile" => $attribute
     * - "description-en_US-ecommerce" => $attribute
     * - "description-fr_FR-mobile" => $attribute
     * - "description-fr_FR-ecommerce" => $attribute
     * - "price-mobile" => $attribute
     * - "price-ecommerce" => $attribute
     * - "name" => $attribute
     */
    public function generate(array $attribute, array $channels): array
    {
        $valueKeys = [];
        $hasValuePerChannel = $attribute['value_per_channel'];
        $hasValuePerLocale = $attribute['value_per_locale'];

        $channelCodes = array_map(function ($channel) {
            return $channel['code'];
        }, $channels);

        $indexedLocaleCodes = array_reduce($channels, function ($indexedLocaleCodes, $channel) {
            $indexedLocaleCodes[$channel['code']] = $channel['locales'];

            return $indexedLocaleCodes;
        }, []);

        $localeCodes = array_unique(array_merge(...array_values($indexedLocaleCodes)));

        if ($hasValuePerChannel && $hasValuePerLocale) {
            foreach ($channelCodes as $channelCode) {
                foreach ($indexedLocaleCodes[$channelCode] as $localeCode) {
                    $key = sprintf(
                        '%s-%s-%s',
                        $attribute['code'],
                        $localeCode,
                        $channelCode
                    );
                    $valueKeys[$key] = $attribute;
                }
            }
        } elseif ($hasValuePerChannel) {
            foreach ($channelCodes as $channelCode) {
                if ($channelCode === $channelCode) {
                    $key = sprintf(
                        '%s-%s',
                        $attribute['code'],
                        $channelCode
                    );
                    $valueKeys[$key] = $attribute;
                }
            }
        } elseif ($hasValuePerLocale) {
            foreach ($localeCodes as $localeCode) {
                $key = sprintf(
                    '%s-%s',
                    $attribute['code'],
                    $localeCode
                );
                $valueKeys[$key] = $attribute;
            }
        } else {
            $key = $attribute['code'];
            $valueKeys[$key] = $attribute;
        }

        return $valueKeys;
    }

    /**
     * Extract "value key" information (attribute, locale & channel) from the given $valueKey for the given $attribute
     * and returns them as an indexed array.
     *
     * eg.: 'description-fr_FR-mobile' will return
     * ['attribute' => 'description', 'channel' => 'mobile', 'locale' => 'fr_FR']
     *
     * @param array  $attribute
     * @param string $valueKey
     *
     * @return array
     */
    public function extract(array $attribute, string $valueKey): array
    {
        $hasValuePerChannel = $attribute['value_per_channel'];
        $hasValuePerLocale = $attribute['value_per_locale'];

        if ($hasValuePerChannel && $hasValuePerLocale) {
            return array_combine(['attribute', 'locale', 'channel'], explode('-', $valueKey));
        } elseif ($hasValuePerLocale) {
            return array_combine(['attribute', 'locale'], explode('-', $valueKey)) + ['channel' => null];
        } elseif ($hasValuePerChannel) {
            return array_combine(['attribute', 'channel'], explode('-', $valueKey)) + ['locale' => null];
        } else {
            return ['attribute' => $valueKey, 'channel' => null, 'locale' => null];
        }
    }
}
