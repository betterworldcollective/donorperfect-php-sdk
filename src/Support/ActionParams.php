<?php

namespace DonorPerfect\Support;

/**
 * Serializes a property bag into DP's `@key=value` action-param format.
 *
 * DP expects single-quoted strings, bare ints/floats, and the bare token `null`.
 * Numeric-looking strings (phone like '+84907921399', zip '12345') get quoted
 * — `is_numeric()` returns true for them and would let them slip through unquoted,
 * which DP's parser rejects with "user not authorized for this api call".
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
            } elseif (is_int($value) || is_float($value)) {
                $params[] = "@{$key}={$value}";
            } else {
                $params[] = "@{$key}='{$value}'";
            }
        }

        return implode(', ', $params);
    }
}
