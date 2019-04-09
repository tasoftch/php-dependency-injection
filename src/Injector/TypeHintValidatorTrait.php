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

namespace TASoft\DI\Injector;

/**
 * Use this trait to obtain callbacks to validate values against type hints (in PHP)
 *
 * @package TASoft\DI\Injector
 */
trait TypeHintValidatorTrait
{
    /**
     * creates a callback to check a value if it matches a type hint (in PHP)
     * @example ->method(string $string, object $object, MyClass $myClass); => accepts strings, accepts any object, accepts instances of MyClass
     *
     * @param string|NULL $type
     * @param bool|NULL $untyped
     * @return \Closure
     */
    protected function getTypeHintValidator(string &$type = NULL, bool &$untyped = NULL) {
        return function($value) use (&$type, &$untyped) {
            switch($type) {
                case 'string': return is_string($value) || !$this->isStrict() && is_object($value) && method_exists($value, '__toString');

                case 'int':
                case 'integer': return is_int($value);

                case 'float':
                case 'double': return is_float($value);

                case 'array': return is_array($value) || !$this->isStrict() && $value instanceof \ArrayAccess;
                case 'object': return is_object($value);
                case 'iterable': return is_iterable($value);
                case 'callable': return is_callable($value);

                default: return $type ? $value instanceof $type : ($untyped?true:false);
            }
        };
    }

    protected function isStrict(): bool {
        return true;
    }
}