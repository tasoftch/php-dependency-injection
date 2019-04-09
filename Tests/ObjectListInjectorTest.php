<?php
/**
 * Copyright (c) 2019 TASoft Applications, Th. Abplanalp <info@tasoft.ch>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

/**
 * ObjectListInjectorTest.php
 * php-dependency-injection
 *
 * Created on 2019-04-09 20:39 by thomas
 */

use TASoft\DI\Injector\ObjectListInjector;
use PHPUnit\Framework\TestCase;

class ObjectListInjectorTest extends TestCase
{

    public function testOffsetExists()
    {
        $inj = new ObjectListInjector([1, 2, 3]);
        $this->assertTrue(isset($inj[1]));
        $this->assertFalse(isset($inj[13]));
    }

    public function testGetDependency()
    {
        $inj = new ObjectListInjector();
        $inj->addObject($this);
        $inj->addObject($inj, 'test');

        $this->assertSame($this, $inj->getDependency(TestCase::class, NULL));
        $this->assertNull($inj->getDependency("Something", NULL));
        $this->assertNull($inj->getDependency(NULL, "Something"));

        $this->assertSame($inj, $inj->getDependency(NULL, 'test'));
    }

    public function testOffsetUnset()
    {
        $inj = new ObjectListInjector([1, 2, 3]);
        unset($inj[1]);
        $this->assertEquals([1, 3], array_values($inj->getList()));
    }

    public function testAddObject()
    {
        $inj = new ObjectListInjector();
        $inj->addObject($this);
        $inj->addObject($inj, 'test');

        $this->assertEquals([0 => $this, 'test' => $inj], $inj->getList());
    }
}
