<?php

/*
 * @copyright   2018 Konstantin Scheumann. All rights reserved
 * @author      Konstantin Scheumann
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticRecaptchaBundle\Tests;

use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\FormBundle\Event\ValidationEvent;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticRecaptchaBundle\EventListener\FormSubscriber;
use MauticPlugin\MauticRecaptchaBundle\Integration\RecaptchaIntegration;
use MauticPlugin\MauticRecaptchaBundle\Service\RecaptchaClient;
use PHPUnit_Framework_MockObject_MockBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FormSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RecaptchaIntegration
     */
    protected $integration;

    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * @var ModelFactory
     */
    protected $modelFactory;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    protected function setUp()
    {
        parent::setUp();

        $this->integration = $this->getMockBuilder(RecaptchaIntegration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->integration
            ->method('getKeys')
            ->willReturn(['site_key' => 'test', 'secret_key' => 'test']);

        $this->eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventDispatcher
            ->method('addListener')
            ->willReturn(true);

        $this->integrationHelper = $this->getMockBuilder(IntegrationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->integrationHelper
            ->method('getIntegrationObject')
            ->willReturn($this->integration);

        $this->modelFactory = $this->getMockBuilder(ModelFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testOnFormValidateSuccessful()
    {
        /** @var RecaptchaClient $recaptchaClient */
        $recaptchaClient = $this->getMockBuilder(RecaptchaClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $recaptchaClient
            ->method('verify')
            ->willReturn(true);

        $formSubscriber = new FormSubscriber(
            $this->eventDispatcher,
            $this->integrationHelper,
            $this->modelFactory,
            $recaptchaClient
        );

        /** @var PHPUnit_Framework_MockObject_MockBuilder|ValidationEvent $validationEvent */
        $validationEvent = $this->getMockBuilder(ValidationEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $validationEvent
            ->method('getValue')
            ->willReturn('any-value-should-work');
        $validationEvent
            ->expects($this->never())
            ->method('failedValidation');

        $formSubscriber->onFormValidate($validationEvent);
    }

    public function testOnFormValidateFailure()
    {
        /** @var RecaptchaClient $recaptchaClient */
        $recaptchaClient = $this->getMockBuilder(RecaptchaClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $recaptchaClient
            ->method('verify')
            ->willReturn(false);

        $formSubscriber = new FormSubscriber(
            $this->eventDispatcher,
            $this->integrationHelper,
            $this->modelFactory,
            $recaptchaClient
        );

        /** @var PHPUnit_Framework_MockObject_MockBuilder|ValidationEvent $validationEvent */
        $validationEvent = $this->getMockBuilder(ValidationEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $validationEvent
            ->method('getValue')
            ->willReturn('any-value-should-work');
        $validationEvent
            ->expects($this->once())
            ->method('failedValidation');

        $formSubscriber->onFormValidate($validationEvent);
    }
}
