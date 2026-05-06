<?php

namespace DonorPerfect\Support;

/**
 * Serializes a property bag into DP's `@key=value` action-param format.
 *
 * Used by every request class that calls a `dp_*_xml` action endpoint
 * (SaveDonor, SaveGift, SaveUdf, SaveFlag). DP expects strings to be
 * single-quoted, numerics bare, and nulls as the bare token `null`.
 */
final class ActionParams
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public static function serialize(array $properties): string
    {
        $params = [];
        foreach ($properties as $key => $value) {
            if ($value === null) {
                $params[] = "@{$key}=null";
            } elseif (is_numeric($value)) {
                $params[] = "@{$key}={$value}";
            } else {
                $params[] = "@{$key}='{$value}'";
            }
        }

        return implode(', ', $params);
    }
}
