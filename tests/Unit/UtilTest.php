<?php

declare(strict_types=1);

namespace EliasHaeussler\ComposerUpdateReporter\Tests\Unit;

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

use EliasHaeussler\ComposerUpdateReporter\Util;

/**
 * UtilTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class UtilTest extends AbstractTestCase
{
    /**
     * @test
     * @dataProvider uppercaseToUnderscoredReturnsUnderscoredStringDataProvider
     */
    public function uppercaseToUnderscoredReturnsUnderscoredString(string $string, string $expected): void
    {
        static::assertSame($expected, Util::camelCaseToUnderscored($string));
    }

    /**
     * @test
     * @dataProvider arrayDiffRecursiveReturnsDiffBetweenArraysDataProvider
     *
     * @param array<string, mixed> $array1
     * @param array<string, mixed> $array2
     * @param array<string, mixed> $expected
     */
    public function arrayDiffRecursiveReturnsDiffBetweenArrays(array $array1, array $array2, array $expected): void
    {
        static::assertSame($expected, Util::arrayDiffRecursive($array1, $array2));
    }

    /**
     * @return array<string, array>
     */
    public function uppercaseToUnderscoredReturnsUnderscoredStringDataProvider(): array
    {
        return [
            'only lowercase' => [
                'foo',
                'foo',
            ],
            'only uppercase' => [
                'FOO',
                'F_O_O',
            ],
            'camel case' => [
                'FooBaz',
                'Foo_Baz',
            ],
        ];
    }

    /**
     * @return array<string, array>
     */
    public function arrayDiffRecursiveReturnsDiffBetweenArraysDataProvider(): array
    {
        return [
            'empty arrays' => [
                [],
                [],
                [],
            ],
            'empty first array' => [
                [],
                ['foo' => 'baz'],
                [],
            ],
            'empty second array' => [
                ['foo' => 'baz'],
                [],
                ['foo' => 'baz'],
            ],
            'diff on first level' => [
                ['foo' => 'baz'],
                ['baz' => 'foo'],
                ['foo' => 'baz'],
            ],
            'diff on deeper level' => [
                ['foo' => ['baz' => 'bummer']],
                ['foo' => ['foo' => 'bummer']],
                ['foo' => ['baz' => 'bummer']],
            ],
            'equal multi-dimensional arrays' => [
                ['foo' => ['baz' => 'bummer']],
                ['foo' => ['baz' => 'bummer']],
                [],
            ],
        ];
    }
}
