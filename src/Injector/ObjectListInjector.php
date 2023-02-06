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
 * Class ObjectListInjector accepts a named, indexed or mixed list of values.
 * If the dependency requires a name and the list has such a name, the dependency is returned.
 * If not, it will iterate over the list to find a value of expected type (if type is set)
 *
 * @package TASoft\DI\Injector
 */
class ObjectListInjector implements InjectorInterface, \ArrayAccess
{
    use TypeHintValidatorTrait;

    private $list = [];

    /**
     * ObjectListInjector constructor.
     * @param array $list
     */
    public function __construct(array $list = [])
    {
        $this->list = $list;
    }

    /**
     * Adds an object to the list
     *
     * @param $object
     * @param string|NULL $name
     * @return ObjectListInjector
     */
    public function addObject($object, string $name = NULL): ObjectListInjector {
        if(is_object($object)) {
            if($name)
                $this->list[$name] = $object;
            else
                $this->list[] = $object;
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getDependency(?string $type, ?string $name)
    {
        if($name && isset($this->list[$name])) {
            return $this->list[$name];
        }

        if($type) {
            $checker = $this->getTypeHintValidator($type);

            foreach($this->list as $value) {
                if($checker($value))
                    return $value;
            }
        }

        return NULL;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->list[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->list[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        if(NULL === $offset)
            $this->list[] = $value;
        else
           $this->list[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        if($this->offsetExists($offset))
            unset($this->list[$offset]);
    }

    /**
     * @return array
     */
    public function getList(): array
    {
        return $this->list;
    }
}