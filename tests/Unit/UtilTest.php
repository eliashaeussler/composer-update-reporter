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
 * UtilTest
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class UtilTest extends AbstractTestCase
{
    /**
     * @test
     * @dataProvider arrayDiffRecursiveReturnsDiffBetweenArraysDataProvider
     * @param array $array1
     * @param array $array2
     * @param array $expected
     */
    public function arrayDiffRecursiveReturnsDiffBetweenArrays(array $array1, array $array2, array $expected): void
    {
        static::assertSame($expected, Util::arrayDiffRecursive($array1, $array2));
    }

    /**
     * @test
     * @dataProvider trimExplodeReturnsArrayOfExplodedTrimmedItemsDataProvider
     * @param string $string
     * @param array $expected
     */
    public function trimExplodeReturnsArrayOfExplodedTrimmedItems(string $string, array $expected): void
    {
        static::assertSame($expected, Util::trimExplode(',', $string));
    }

    /**
     * @test
     */
    public function trimExplodeReturnsLimitedExplodedArray(): void
    {
        $string = ' foo   ,  baz, boo';
        $expected = [
            'foo',
            'baz, boo',
        ];
        static::assertSame($expected, Util::trimExplode(',', $string, 2));
    }

    /**
     * @test
     */
    public function trimExplodeFillsArrayWithNullValues(): void
    {
        $string = 'foo, baz';
        $expected = [
            'foo',
            'baz',
            null,
            null,
        ];
        static::assertSame($expected, Util::trimExplode(',', $string, 4, true));
    }

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

    public function trimExplodeReturnsArrayOfExplodedTrimmedItemsDataProvider(): array
    {
        return [
            'empty string' => [
                '',
                [''],
            ],
            'whitespaces only' => [
                '      ',
                [''],
            ],
            'string with whitespaced items' => [
                ' foo  ,  baz  ,   ',
                ['foo', 'baz', ''],
            ],
        ];
    }
}
