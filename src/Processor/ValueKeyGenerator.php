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
     * and return them as an array of strings.
     *
     * Ie.:
     * - "description-en_US-mobile"
     * - "description-en_US-ecommerce"
     * - "description-fr_FR-mobile"
     * - "description-fr_FR-ecommerce"
     * - "price-mobile"
     * - "price-ecommerce"
     * - "name"
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
                    $valueKeys[] = sprintf(
                        '%s-%s-%s',
                        $attribute['code'],
                        $localeCode,
                        $channelCode
                    );
                }
            }
        } elseif ($hasValuePerChannel) {
            foreach ($channelCodes as $channelCode) {
                if ($channelCode === $channelCode) {
                    $valueKeys[] = sprintf(
                        '%s-%s',
                        $attribute['code'],
                        $channelCode
                    );
                }
            }
        } elseif ($hasValuePerLocale) {
            foreach ($localeCodes as $localeCode) {
                $valueKeys[] = sprintf(
                    '%s-%s',
                    $attribute['code'],
                    $localeCode
                );
            }
        } else {
            $valueKeys[] = $attribute['code'];
        }

        return $valueKeys;
    }

    /**
     * Extract "value key" information (attribute, locale & channel) from the given $valueKey for the given $attribute
     * and returns them as an indexed array.
     *
     * ie.: ['attribute' => 'description', 'channel' => 'mobile', 'locale' => 'fr_FR']
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
