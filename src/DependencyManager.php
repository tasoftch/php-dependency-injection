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
use TASoft\DI\Injector\InjectorInterface;
use TASoft\PHP\SignatureService;

class DependencyManager
{
    /** @var PriorityCollection */
    private $dependencyInjectors;
    /** @var SignatureService */
    private $methodSignatureService;

    public function __construct(iterable $dependencyInjectors, SignatureService $methodSignatureService = NULL)
    {
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
        $this->dependencyInjectors->add($priority, $injector);
    }

    /**
     * Removes a dependency injector to dependency manager
     * @param InjectorInterface $injector
     */
    public function removeDependencyInjector(InjectorInterface $injector) {
        $this->dependencyInjectors->remove($injector);
    }

    /**
     * Clears all dependency injectors
     */
    public function clearDependencyInjectors() {
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
}