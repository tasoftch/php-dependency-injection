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


use Traversable;

class FunctionArgumentInjector implements InjectorInterface
{
    /** @var array */
    private $arguments = [];
    private $namedArguments = [];

    /**
     * FunctionArgumentInjector constructor.
     * @param iterable|mixed[] $arguments   Accepts an iterable or arguments
     */
    public function __construct(...$arguments)
    {
        $addType = function($type) use (&$argument) {
            $type = strtoupper($type);

            if(isset($this->arguments[$type])) {
                if($type != 'OBJECT')
                    trigger_error("Argument of type $type already exists", E_USER_WARNING);
                return;
            }

            $this->arguments[$type] = $argument;
        };

        // If passed one iterable, treat it as arguments
        if(count($arguments) == 1 && is_array($arguments[0]))
            $arguments = $arguments[0];

        foreach($arguments as $key => $argument) {
            $type = gettype($argument);

            if($type == 'integer')
                $type = 'int';
            if($type == 'double')
                $type = 'float';
            if($type == 'bool')
                $type = 'boolean';

            if($type == 'object') {
                $class = get_class($argument);
                do {
                    $addType($class);
                    $class = get_parent_class($class);
                } while ($class);
            }

            if(is_string($key))
                $this->namedArguments[$key] = $argument;

            $addType($type);
        }
    }

    /**
     * @inheritDoc
     */
    public function getDependency(?string $type, ?string $name)
    {
        if($name && isset($this->namedArguments[$name]))
            return $this->namedArguments[$name];

        do {
            if(isset($this->arguments[ strtoupper($type?:"") ])) {
                return $this->arguments[ strtoupper($type?:"") ];
            }
            $type = $type ? get_parent_class($type) : NULL;
        } while($type);
        return NULL;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return array
     */
    public function getNames(): array {
        return $this->namedArguments;
    }
}