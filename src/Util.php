<?php
declare(strict_types=1);
namespace EliasHaeussler\ComposerUpdateReporter;

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-reporter".
 *
 * Copyright (C) 2020 Elias Häußler <elias@haeussler.dev>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Util
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class Util
{
    public static function arrayDiffRecursive(array $array1, array $array2): array
    {
        $difference = [];
        foreach($array1 as $key => $value) {
            if (!array_key_exists($key, $array2)) {
                $difference[$key] = $value;
            } else if (is_array($value) && is_array($array2[$key])) {
                $recursiveDiff = static::arrayDiffRecursive($value, $array2[$key]);
                if (!empty($recursiveDiff)) {
                    $difference[$key] = $recursiveDiff;
                }
            }
        }
        return $difference;
    }

    public static function trimExplode(string $delimiter, string $string, int $limit = null, bool $fillArray = false): array
    {
        if ($limit !== null) {
            $explodedArray = explode($delimiter, $string, $limit);
        } else {
            $explodedArray = explode($delimiter, $string);
        }
        $result = array_map('trim', $explodedArray);
        if ($fillArray && $limit > count($result)) {
            return array_pad($result, $limit, null);
        }
        return $result;
    }
}
