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
 * TypeHintValidatorTraitTest.php
 * php-dependency-injection
 *
 * Created on 2019-04-09 19:47 by thomas
 */

use PHPUnit\Framework\TestCase;
use TASoft\DI\Injector\TypeHintValidatorTrait;

class TypeHintValidatorTraitTest extends TestCase
{
    public function testLazyTypeHintTrait() {
        $class = new TypeHint();

        $type = "string";
        $untyped = false;

        $handler = $class->getTypeHintValidator($type, $untyped);

        $this->assertTrue($handler("My String"));
        $this->assertTrue($handler(new class {
            public function __toString()
            {
                return "OK";
            }
        }));

        $this->assertFalse($handler(function() {}));
        $this->assertFalse($handler(23));

        $type = 'integer';

        $this->assertTrue($handler(23));
        $this->assertFalse($handler(23.13));

        $type = 'array';
        $this->assertTrue($handler([1, 2, 3]));
        $this->assertTrue($handler(new ArrayObject()));

        $this->assertFalse($handler("my String"));

        $type = 'iterable';
        $this->assertFalse($handler("my String"));
        $this->assertTrue($handler(new ArrayIterator([])));

        $type = 'object';
        $this->assertFalse($handler("my String"));
        $this->assertTrue($handler($this));

        $type = 'callable';
        $this->assertFalse($handler(23));
        $this->assertTrue($handler(function() {}));
        $this->assertTrue($handler( new class {
            public function __invoke()
            {
            }
        } ));

        $type = get_class($this);
        $this->assertFalse($handler(23));
        $this->assertTrue($handler($this));

        $type = NULL;
        $this->assertFalse($handler("My String"));
        $this->assertFalse($handler(23));
        $this->assertFalse($handler(function(){}));
        $this->assertFalse($handler($this));

        $untyped = true;
        $this->assertTrue($handler("My String"));
        $this->assertTrue($handler(23));
        $this->assertTrue($handler(function(){}));
        $this->assertTrue($handler($this));
    }

    public function testStrictTypeHintTrait() {
        $class = new StrictTypeHint();

        $type = "string";
        $untyped = false;

        $handler = $class->getTypeHintValidator($type, $untyped);

        $this->assertTrue($handler("My String"));
        $this->assertFalse($handler(new class {
            public function __toString()
            {
                return "OK";
            }
        }));

        $type = 'array';
        $this->assertTrue($handler([1, 2, 3]));
        $this->assertFalse($handler(new ArrayObject()));
    }
}

class TypeHint {
    use TypeHintValidatorTrait {
        getTypeHintValidator as _getTypeHintValidator;
    }

    public function getTypeHintValidator(string &$type = NULL, bool &$untyped = NULL)
    {
        return $this->_getTypeHintValidator($type, $untyped);
    }

    protected function isStrict(): bool
    {
        return false;
    }
}

class StrictTypeHint {
    use TypeHintValidatorTrait {
        getTypeHintValidator as _getTypeHintValidator;
    }

    public function getTypeHintValidator(string &$type = NULL, bool &$untyped = NULL)
    {
        return $this->_getTypeHintValidator($type, $untyped);
    }
}