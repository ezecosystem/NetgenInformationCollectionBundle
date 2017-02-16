<?php

namespace Netgen\Bundle\InformationCollectionBundle\Tests\Controller;

use eZ\Publish\Core\MVC\Symfony\Controller\Content\ViewController;
use eZ\Publish\Core\Repository\LocationService;
use eZ\Publish\Core\Repository\Values\Content\Location;
use Netgen\Bundle\EzFormsBundle\Form\DataWrapper;
use Netgen\Bundle\InformationCollectionBundle\Form\Builder\FormBuilder;
use PHPUnit\Framework\TestCase;
use Netgen\Bundle\InformationCollectionBundle\Controller\InformationCollectionLegacyController;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

class InformationCollectionLegacyControllerTest extends TestCase
{
    /**
     * @var \Netgen\Bundle\InformationCollectionBundle\Controller\InformationCollectionLegacyController
     */
    protected $controller;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dispatcher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $builder;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $locationService;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $formBuilder;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $form;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $ezContent;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $container;

    public function setUp()
    {
        $this->container = $this->getMockBuilder(ContainerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get', 'getParameter', 'has', 'hasParameter', 'initialized', 'set', 'setParameter', 'addScope', 'enterScope', 'hasScope', 'isScopeActive', 'leaveScope'])
            ->getMock();

        $this->builder = $this->getMockBuilder(FormBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['createFormForLocation'])
            ->getMock();

        $this->dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['dispatch', 'addListener', 'addSubscriber', 'removeListener', 'removeSubscriber', 'getListeners', 'hasListeners', 'getListenerPriority'])
            ->getMock();

        $this->locationService = $this->getMockBuilder(LocationService::class)
            ->disableOriginalConstructor()
            ->setMethods(['loadLocation'])
            ->getMock();

        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->formBuilder = $this->getMockBuilder(\Symfony\Component\Form\FormBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['getForm'])
            ->getMock();

        $this->ezContent = $this->getMockBuilder(ViewController::class)
            ->disableOriginalConstructor()
            ->setMethods(['viewLocation'])
            ->getMock();

        $this->form = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->setMethods(['handleRequest', 'isSubmitted', 'isValid', 'getData', 'createView'])
            ->getMock();

        $this->controller = new InformationCollectionLegacyController();
        $this->controller->setContainer($this->container);

        parent::setUp();
    }

    public function testInstanceOfContainerAwareInterface()
    {
        $this->assertInstanceOf(ContainerAwareInterface::class, $this->controller);
    }

    public function testDisplayAndHandleWithValidFormSubmission()
    {
        $location = new Location(['id' => 123]);

        $this->container->expects($this->exactly(4))
            ->method('get')
            ->with($this->logicalOr(
                $this->equalTo('netgen_information_collection.form.builder'),
                $this->equalTo('event_dispatcher'),
                $this->equalTo('ezpublish.api.service.location'),
                $this->equalTo('ez_content')
            ))
            ->will($this->returnCallback([$this, 'getService']));

        $this->locationService->expects($this->once())
            ->method('loadLocation')
            ->with($location->id)
            ->willReturn($location);

        $this->formBuilder->expects($this->once())
            ->method('getForm')
            ->willReturn($this->form);

        $this->builder->expects($this->once())
            ->method('createFormForLocation')
            ->with($location, false)
            ->willReturn($this->formBuilder);

        $this->form->expects($this->once())
            ->method('handleRequest')
            ->with($this->request);

        $this->form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->form->expects($this->once())
            ->method('getData')
            ->willReturn(new DataWrapper('payload'));

        $this->form->expects($this->once())
            ->method('createView');

        $this->dispatcher->expects($this->once())
            ->method('dispatch');

        $this->controller->displayAndHandle($this->request, $location->id, 'full');
    }

    public function testDisplayAndHandleWithInvalidFormSubmission()
    {
        $location = new Location(['id' => 123]);

        $this->container->expects($this->exactly(3))
            ->method('get')
            ->with($this->logicalOr(
                $this->equalTo('netgen_information_collection.form.builder'),
                $this->equalTo('event_dispatcher'),
                $this->equalTo('ezpublish.api.service.location'),
                $this->equalTo('ez_content')
            ))
            ->will($this->returnCallback([$this, 'getService']));

        $this->locationService->expects($this->once())
            ->method('loadLocation')
            ->with($location->id)
            ->willReturn($location);

        $this->formBuilder->expects($this->once())
            ->method('getForm')
            ->willReturn($this->form);

        $this->builder->expects($this->once())
            ->method('createFormForLocation')
            ->with($location, false)
            ->willReturn($this->formBuilder);

        $this->form->expects($this->once())
            ->method('handleRequest')
            ->with($this->request);

        $this->form->expects($this->never())
            ->method('isSubmitted');

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->form->expects($this->never())
            ->method('getData');

        $this->form->expects($this->once())
            ->method('createView');

        $this->dispatcher->expects($this->never())
            ->method('dispatch');

        $this->controller->displayAndHandle($this->request, $location->id, 'full');
    }

    public function getService($id)
    {
        switch ($id) {
            case 'netgen_information_collection.form.builder':
                return $this->builder;
            case 'event_dispatcher':
                return $this->dispatcher;
            case 'ezpublish.api.service.location':
                return $this->locationService;
            case 'ez_content':
                return $this->ezContent;
        }
    }
}
