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
 * DependencyManagerTest.php
 * php-dependency-injection
 *
 * Created on 2019-04-09 21:09 by thomas
 */

use PHPUnit\Framework\TestCase;
use TASoft\DI\DependencyManager;
use TASoft\DI\Exception\DependencyException;
use TASoft\DI\Injector\CallbackInjector;
use TASoft\DI\Injector\ObjectListInjector;
use TASoft\DI\Injector\ObjectPropertyInjector;
use TASoft\DI\Injector\ServiceInjector;
use TASoft\PHP\SignatureService;
use TASoft\Service\ServiceManager;

class DependencyManagerTest extends TestCase
{
    public function testInjectorManipulations() {
        $inj1 = new ObjectPropertyInjector($this);
        $inj2 = new CallbackInjector(function() {});
        $inj3 = new ObjectListInjector();
        $inj4 = new ServiceInjector(ServiceManager::generalServiceManager([]));

        $dm = new DependencyManager([$inj3]);


        $dm->addDependencyInjector($inj1, 30);
        $dm->addDependencyInjector($inj2, 10);
        $dm->addDependencyInjector($inj4, 40);

        $this->assertEquals([$inj2, $inj1, $inj4, $inj3], $dm->getOrderedDependencyInjectors());

        $dm->removeDependencyInjector($inj2);
        $this->assertEquals([$inj1, $inj4, $inj3], $dm->getOrderedDependencyInjectors());

        $dm->clearDependencyInjectors();
        $this->assertEquals([], $dm->getOrderedDependencyInjectors());
    }

    public function testCallStringFunction() {
        $dm = new DependencyManager();

        $dm->addDependencyInjector(new CallbackInjector(function($t, $n) {
            if($n == 'argument')
                return 99;
            if($n == 'test')
                return "Here I am";
            return NULL;
        }));

        $result = $dm->call("my_test_func");
        $this->assertEquals([99, "Here I am", false], $result);
    }

    public function testCallClosureFunction() {
        $dm = new DependencyManager();

        $dm->addDependencyInjector(new CallbackInjector(function($t, $n) {
            if($n == 'argument')
                return 99;
            if($t == 'bool')
                return TRUE;
            return NULL;
        }));

        $result = $dm->call(function(bool $haha, $argument, string $test = "") {
            return func_get_args();
        });
        $this->assertEquals([TRUE, 99, ""], $result);
    }

    public function testCallInvokable() {
        $dm = new DependencyManager();

        $dm->addDependencyInjector(new CallbackInjector(function($t, $n) {
            if($n == 'argument')
                return 99;
            if($t == 'bool')
                return TRUE;
            return NULL;
        }));

        $result = $dm->call(new class {
            public function __invoke($argument, bool $flag, ?string $object)
            {
                return func_get_args();
            }
        });
        $this->assertEquals([99, TRUE, NULL], $result);
    }

    /**
     * @expectedException TASoft\DI\Exception\DependencyException
     */
    public function testCallInvalid() {
        $dm = new DependencyManager();

		$this->expectException(DependencyException::class);
        $result = $dm->call(new class {

        });
    }

    public function testCreateObjectInstance() {
        $dm = new DependencyManager();

        $dm->addDependencyInjector(new CallbackInjector(function($t, $n) {
            if($n == 'argument')
                return 99;
            if($t == 'bool')
                return TRUE;
            return NULL;
        }));

        $result = $dm->call(MyCLClass::class);
        $this->assertInstanceOf(MyCLClass::class, $result);

        $result = $dm->call(MyCCClass::class);
        $this->assertInstanceOf(MyCCClass::class, $result);

        $result = $dm->call(MyCCClass1::class);
        $this->assertInstanceOf(MyCCClass1::class, $result);
        $this->assertTrue($result->hello);

        $result = $dm->call('getcwd');
        $this->assertEquals(getcwd(), $result);
    }

    /**
     * @expectedException TASoft\DI\Exception\UnresolvedArgumentException
     */
    public function testUnresolvableArgument() {
        $dm = new DependencyManager();
        $dm->setMethodSignatureService( SignatureService::getSignatureService() );

        $dm->addDependencyInjector(new CallbackInjector(function($t, $n) {
            if($n == 'argument')
                return 99;
            if($t == 'bool')
                return TRUE;
            return NULL;
        }));

		$this->expectException(\TASoft\DI\Exception\UnresolvedArgumentException::class);

        $dm->call(function(string $hello) {});
    }

    public function testPushingGroup() {
        $dm = new DependencyManager();
        $dm->addDependencyInjector(new CallbackInjector(function($t, $n) {
            if($n == 'argument')
                return 99;
            return NULL;
        }));

        $func = function($argument, string $value = ""){return func_get_args();};

        $this->assertEquals([99, ""], $dm->call($func));

        $result = $dm->pushGroup(function() use ($dm, $func) {
            $dm->addDependencyInjector(new CallbackInjector(function($t, $n) {
                return $t == 'string' ? 'String' : NULL;
            }));

            return $dm->call($func);
        });

        $this->assertEquals([99, 'String'], $result);
        $this->assertEquals([99, ""], $dm->call($func));
    }

    public function testIsolatedGroup() {
        $dm = new DependencyManager();
        $dm->addDependencyInjector(new CallbackInjector(function($t, $n) {
            if($n == 'argument')
                return 99;
            return NULL;
        }));

        $func = function($argument = 3, string $value = ""){return func_get_args();};

        $this->assertEquals([99, ""], $dm->call($func));

        $result = $dm->isolateGroup(function() use ($dm, $func) {
            $dm->addDependencyInjector(new CallbackInjector(function($t, $n) {
                return $t == 'string' ? 'String' : NULL;
            }));

            return $dm->call($func);
        });

        $this->assertEquals([3, 'String'], $result);
        $this->assertEquals([99, ""], $dm->call($func));
    }
}

function my_test_func(int $argument, string $test, bool $flag = false) {
    return func_get_args();
}

class MyCLClass {

}

class MyCCClass {
    public function __construct()
    {

    }
}

class MyCCClass1 {
    public $hello;
    public function __construct(bool $hello)
    {
        $this->hello = $hello;
    }
}
