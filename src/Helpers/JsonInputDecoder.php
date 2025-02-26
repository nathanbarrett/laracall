<?php

namespace NathanBarrett\LaraCall\Helpers;

/**
 * Class JsonInputDecoder
 *
 * This class provides functionality to decode JSON strings that may have missing quotes around keys and string values.
 * When a JSON string is passed in the cli like --option={"key": "value"} is seen in the command as {key: value} which is not valid JSON.
 */
class JsonInputDecoder {
    /**
     * Fixes a JSON string with missing quotes and decodes it.
     * Returns the decoded array if successful and non-empty, otherwise the original string.
     *
     * @param string $str The input JSON string with potentially missing quotes
     * @return string|array The decoded array or original string
     */
    public static function fixAndDecode(string $str): string|array {
        $trimmed = trim($str);
        // Check if the string starts with '{' and ends with '}' or starts with '[' and ends with ']'
        if ((str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')) ||
            (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']'))) {
            $fixedStr = self::fixJson($trimmed);
            $decoded = json_decode($fixedStr, true);
            // Return decoded array only if it's an array and non-empty
            if (is_array($decoded) && !empty($decoded)) {
                return $decoded;
            }
        }
        return $str;
    }

    /**
     * Determines if the string is an object or array and fixes it accordingly.
     *
     * @param string $str The trimmed JSON string
     * @return string The fixed JSON string
     */
    private static function fixJson(string $str): string {
        if (str_starts_with($str, '{')) {
            return self::fixObject(substr($str, 1, -1));
        } elseif (str_starts_with($str, '[')) {
            return self::fixArray(substr($str, 1, -1));
        }
        return $str; // Fallback, should not occur due to prior check
    }

    /**
     * Fixes a JSON object by adding quotes to keys and string values.
     *
     * @param string $innerStr The content inside the curly braces
     * @return string The fixed object string
     */
    private static function fixObject(string $innerStr): string {
        $pairs = self::splitTopLevel($innerStr, ',');
        $fixedPairs = [];
        foreach ($pairs as $pair) {
            $colonPos = strpos($pair, ':');
            if ($colonPos === false) {
                continue; // Skip malformed pairs
            }
            $key = trim(substr($pair, 0, $colonPos));
            $value = trim(substr($pair, $colonPos + 1));
            $fixedKey = '"' . $key . '"';
            $fixedValue = self::fixValue($value);
            $fixedPairs[] = $fixedKey . ':' . $fixedValue;
        }
        return '{' . implode(',', $fixedPairs) . '}';
    }

    /**
     * Fixes a JSON array by processing each element.
     *
     * @param string $innerStr The content inside the square brackets
     * @return string The fixed array string
     */
    private static function fixArray(string $innerStr): string {
        $elements = self::splitTopLevel($innerStr, ',');
        $fixedElements = [];
        foreach ($elements as $element) {
            $fixedElements[] = self::fixValue(trim($element));
        }
        return '[' . implode(',', $fixedElements) . ']';
    }

    /**
     * Fixes a single value based on its type.
     *
     * @param string $value The value to fix
     * @return string The fixed value
     */
    private static function fixValue(string $value): string {
        $value = trim($value);
        if ($value === 'true' || $value === 'false' || $value === 'null') {
            return $value; // Booleans and null remain unquoted
        } elseif (is_numeric($value)) {
            return $value; // Numbers remain unquoted
        } elseif (str_starts_with($value, '{')) {
            $endPos = self::getMatchingBrace($value, 0);
            if ($endPos !== false) {
                $inner = substr($value, 1, $endPos - 1);
                return self::fixObject($inner); // Recursively fix nested object
            }
        } elseif (str_starts_with($value, '[')) {
            $endPos = self::getMatchingBrace($value, 0);
            if ($endPos !== false) {
                $inner = substr($value, 1, $endPos - 1);
                return self::fixArray($inner); // Recursively fix nested array
            }
        }
        // Treat as string and add quotes
        return '"' . $value . '"';
    }

    /**
     * Splits a string on a separator, respecting nested structures.
     *
     * @param string $str The string to split
     * @param string $separator The separator character
     * @return array<string> The array of split parts
     */
    private static function splitTopLevel(string $str, string $separator): array {
        $parts = [];
        $start = 0;
        $level = 0;
        for ($i = 0; $i < strlen($str); $i++) {
            $char = $str[$i];
            if ($char === '{' || $char === '[') {
                $level++;
            } elseif ($char === '}' || $char === ']') {
                $level--;
            } elseif ($char === $separator && $level === 0) {
                $parts[] = substr($str, $start, $i - $start);
                $start = $i + 1;
            }
        }
        if ($start < strlen($str)) {
            $parts[] = substr($str, $start);
        }
        return $parts;
    }

    /**
     * Finds the position of the matching closing brace or bracket.
     *
     * @param string $str The string to search
     * @param int $startPos The position of the opening brace/bracket
     * @return int|false The position of the matching close or false if not found
     */
    private static function getMatchingBrace(string $str, int $startPos): int|false {
        $open = $str[$startPos];
        $close = ($open === '{') ? '}' : ']';
        $level = 1;
        for ($i = $startPos + 1; $i < strlen($str); $i++) {
            $char = $str[$i];
            if ($char === $open) {
                $level++;
            } elseif ($char === $close) {
                $level--;
                if ($level === 0) {
                    return $i;
                }
            }
        }
        return false;
    }
}
