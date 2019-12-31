<?php

/**
 * @see       https://github.com/laminas/laminas-mvc-middleware for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc-middleware/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc-middleware/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Middleware\Integration;

use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Laminas\Diactoros\Response;
use Laminas\Mvc\Controller\MiddlewareController as DeprecatedMiddlewareController;
use Laminas\Mvc\Middleware\MiddlewareController;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\Literal;
use LaminasTest\Mvc\Middleware\TestAsset\Middleware;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use stdClass;

/**
 * @group integration
 * @coversNothing
 */
class MiddlewareDispatchTest extends TestCase
{
    use ApplicationTrait;

    protected function setUp()
    {
        parent::setUp();
        $this->setUpApplication([
            'router' => [
                'routes' => [
                    'middleware' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/middleware',
                            'defaults' => [
                                'middleware' => 'MiddlewareMock',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    protected function tearDown()
    {
        $this->tearDownApplication();
        parent::tearDown();
    }

    public function testDispatchesMiddleware()
    {
        $services = $this->application->getServiceManager();

        $services->get('Request')->setUri('http://example.local/middleware');

        $middlewareMock = $this->prophesize(MiddlewareInterface::class);
        $middlewareMock->process(Argument::cetera())
            ->willReturn(new Response())
            ->shouldBeCalled();
        $services->setService('MiddlewareMock', $middlewareMock->reveal());

        $this->application->run();
    }

    public function testMiddlewareDispatchTriggersSharedEventOnMiddlewareController()
    {
        $sharedEm = $this->application->getEventManager()->getSharedManager();
        $services = $this->application->getServiceManager();
        $services->get('Request')->setUri('http://example.local/middleware');
        $services->setService('MiddlewareMock', new Middleware());

        $listener = $this->getMockBuilder(stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();
        $listener->expects(self::atLeastOnce())->method('__invoke');
        /** @var callable $listener */
        $sharedEm->attach(MiddlewareController::class, MvcEvent::EVENT_DISPATCH, $listener);

        $this->application->run();
    }

    public function testMiddlewareDispatchTriggersSharedEventOnOldMiddlewareController()
    {
        $sharedEm = $this->application->getEventManager()->getSharedManager();
        $services = $this->application->getServiceManager();
        $services->get('Request')->setUri('http://example.local/middleware');
        $services->setService('MiddlewareMock', new Middleware());

        $listener = $this->getMockBuilder(stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();
        $listener->expects(self::atLeastOnce())->method('__invoke');
        /** @var callable $listener */
        $sharedEm->attach(DeprecatedMiddlewareController::class, MvcEvent::EVENT_DISPATCH, $listener);

        $this->application->run();
    }
}
