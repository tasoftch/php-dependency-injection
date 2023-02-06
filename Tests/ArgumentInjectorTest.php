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
 * ArgumentInjectorTest.php
 * php-dependency-injection
 *
 * Created on 2019-05-27 17:16 by thomas
 */

use PHPUnit\Framework\Error\Warning;
use PHPUnit\Framework\TestCase;
use TASoft\DI\Injector\FunctionArgumentInjector;

class ArgumentInjectorTest extends TestCase
{
    public function testScalarArgumentInjector() {
        $ai = new FunctionArgumentInjector(
            1, "Test", 0.5, true, NULL
        );

        $this->assertEquals([
            'INT' => 1,
            "STRING" => "Test",
            "FLOAT" => 0.5,
            "BOOLEAN" => true,
            "NULL" => NULL
        ], $ai->getArguments());
    }

    /**
     * @expectedException PHPUnit\Framework\Error\Warning
     */
    public function testDuplicatedArguments() {
		$this->expectException(Warning::class);
        $ai = new FunctionArgumentInjector(
            1, 2, 3
        );
    }

    public function testNamedArguments() {
        $ai = new FunctionArgumentInjector([
            'test' => 23,
            'String',
            'flag' => false
        ]);

        $this->assertEquals([
            "INT" => 23,
            "STRING" => "String",
            "BOOLEAN" => false
        ], $ai->getArguments());

        $this->assertEquals([
            'test' => 23,
            'flag' => false
        ], $ai->getNames());
    }

    public function testObjectArguments() {
        $ai = new FunctionArgumentInjector(
            $o1 = new stdClass,
            $o2 = new FunctionArgumentInjector()
        );

        $this->assertEquals([
            'STDCLASS' => $o1,
            'OBJECT' => $o1,
            'TASOFT\DI\INJECTOR\FUNCTIONARGUMENTINJECTOR' => $o2
        ], $ai->getArguments());

        $this->assertEquals($o1, $ai->getDependency('object', NULL));
        $this->assertEquals($o1, $ai->getDependency('object', 'unknown name'));

        $this->assertEquals($o2, $ai->getDependency(FunctionArgumentInjector::class, "hehe"));
        $this->assertEquals($o2, $ai->getDependency(FunctionArgumentInjector::class, NULL));

        $this->assertNull($ai->getDependency(NULL, NULL));
        $this->assertNull($ai->getDependency(ArgumentInjectorTest::class, NULL));
    }
}
