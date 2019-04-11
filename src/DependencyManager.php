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

namespace TASoft\DI;


use TASoft\Collection\PriorityCollection;
use TASoft\DI\Exception\DependencyException;
use TASoft\DI\Exception\UnresolvedArgumentException;
use TASoft\DI\Injector\InjectorInterface;
use TASoft\PHP\Attribute\ArgumentValue;
use TASoft\PHP\Signature\MethodSignature;
use TASoft\PHP\SignatureService;

class DependencyManager
{
    /** @var PriorityCollection */
    private $dependencyInjectors;

    /** @var SignatureService */
    private $methodSignatureService;

    /** @var PriorityCollection[] */
    private $groupedDependencyInjectors = [];

    public function __construct(iterable $dependencyInjectors = [], SignatureService $methodSignatureService = NULL)
    {
        $this->dependencyInjectors = new PriorityCollection();

        foreach($dependencyInjectors as $injector) {
            $this->addDependencyInjector($injector);
        }
        $this->methodSignatureService = $methodSignatureService;
    }

    /**
     * Adds a dependency injector to dependency manager
     *
     * @param InjectorInterface $injector
     * @param int $priority
     */
    public function addDependencyInjector(InjectorInterface $injector, int $priority = 100) {
        if($this->groupedDependencyInjectors) {
            end($this->groupedDependencyInjectors)->add($priority, $injector);
            return;
        }
        $this->dependencyInjectors->add($priority, $injector);
    }

    /**
     * Removes a dependency injector to dependency manager
     * @param InjectorInterface $injector
     */
    public function removeDependencyInjector(InjectorInterface $injector) {
        if($this->groupedDependencyInjectors) {
            end($this->groupedDependencyInjectors)->remove($injector);
            return;
        }
        $this->dependencyInjectors->remove($injector);
    }

    public function getOrderedDependencyInjectors() {
        if($this->groupedDependencyInjectors) {
            return end($this->groupedDependencyInjectors)->getOrderedElements();
        }
        return $this->dependencyInjectors->getOrderedElements();
    }

    /**
     * Clears all dependency injectors
     */
    public function clearDependencyInjectors() {
        if($this->groupedDependencyInjectors) {
            end($this->groupedDependencyInjectors)->clear();
            return;
        }
        $this->dependencyInjectors->clear();
    }

    /**
     * @return SignatureService
     */
    public function getMethodSignatureService(): SignatureService
    {
        if(!$this->methodSignatureService)
            $this->methodSignatureService = SignatureService::getSignatureService();
        return $this->methodSignatureService;
    }

    /**
     * @param SignatureService $methodSignatureService
     */
    public function setMethodSignatureService(SignatureService $methodSignatureService): void
    {
        $this->methodSignatureService = $methodSignatureService;
    }

    /**
     * Tries to obtain a dependency from injectors
     *
     * @param string|NULL $type
     * @param string|NULL $name
     */
    public function getDependency(string $type = NULL, string $name = NULL) {
        /** @var InjectorInterface $injector */
        foreach($this->getOrderedDependencyInjectors() as $injector) {
            if($dep = $injector->getDependency($type, $name))
                return $dep;
        }
        return NULL;
    }

    /**
     * Main method of dependency manager. It will resolve the $symbol and inject required arguments.
     * Everything that SignatureService accepts is allowed as symbol
     * You can call a string with a classname to create a new instance of MyClass under dependency injection
     *
     * @param callable|array $symbol
     * @return mixed
     * @see SignatureService::getSignature()
     */
    public function call($symbol) {
        $signature = $this->getMethodSignatureService()->getSignature($symbol);
        if($signature) {
            $arguments = [];

            /** @var ArgumentValue $declaration */
            foreach($signature as $declaration) {
                $dep = $this->getDependency($declaration->getValue(), $declaration->getName());
                if(!$dep) {
                    if($declaration->isOptional())
                        $arguments[] = $declaration->getDefaultValue();
                    elseif($declaration->allowsNull())
                        $arguments[] = NULL;
                    else {
                        $e = new UnresolvedArgumentException("Could not resolve dependency for argument %s", 0, NULL, $declaration->getName());
                        $e->setArgumentDeclaration($declaration);
                        throw $e;
                    }
                } else {
                    $arguments[] = $dep;
                }
            }

            if($signature instanceof MethodSignature && $signature->getQualifiedName() == '__construct') {
                $className = $signature->getClassName();
                if($arguments)
                    return new $className(...$arguments);
                else
                    return new $className();
            } elseif($arguments)
                return call_user_func_array($symbol, $arguments);
            else
                return call_user_func($symbol);
        }
        throw new DependencyException("Invalid symbol. Can not resolve any callable");
    }

    private function _execGroup(callable $goupedCode) {
        try {
            return $goupedCode();
        } catch (\Throwable $exception) {
            throw $exception;
        } finally {
            array_pop($this->groupedDependencyInjectors);
        }
    }

    public function pushGroup(callable $groupedCode) {
        if($this->groupedDependencyInjectors)
            $last = end($this->groupedDependencyInjectors);
        else
            $last = $this->dependencyInjectors;

        $this->groupedDependencyInjectors[] = new PriorityCollection(0, $last);
        return $this->_execGroup($groupedCode);
    }

    public function isolateGroup(callable $groupedCode) {
        $this->groupedDependencyInjectors[] = new PriorityCollection();
        return $this->_execGroup($groupedCode);
    }
}