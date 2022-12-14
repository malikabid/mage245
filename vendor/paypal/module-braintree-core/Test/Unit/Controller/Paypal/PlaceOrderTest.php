<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace PayPal\Braintree\Test\Unit\Controller\Paypal;

use PayPal\Braintree\Controller\Paypal\PlaceOrder;
use PayPal\Braintree\Gateway\Config\PayPal\Config;
use PayPal\Braintree\Model\Paypal\Helper\OrderPlace;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Model\Quote;

/**
 * @see \PayPal\Braintree\Controller\Paypal\PlaceOrder
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PlaceOrderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var OrderPlace|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderPlaceMock;

    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configMock;

    /**
     * @var Session|\PHPUnit\Framework\MockObject\MockObject
     */
    private $checkoutSessionMock;

    /**
     * @var RequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestMock;

    /**
     * @var ResultFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resultFactoryMock;

    /**
     * @var ManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $messageManagerMock;

    /**
     * @var PlaceOrder
     */
    private $placeOrder;

    protected function setUp(): void
    {
        /** @var Context|\PHPUnit\Framework\MockObject\MockObject $contextMock */
        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPostValue'])
            ->getMockForAbstractClass();
        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderPlaceMock = $this->getMockBuilder(OrderPlace::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageManagerMock = $this->getMockBuilder(ManagerInterface::class)
            ->getMockForAbstractClass();

        $contextMock->expects(self::once())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $contextMock->expects(self::once())
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);
        $contextMock->expects(self::once())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);

        $this->placeOrder = new PlaceOrder(
            $contextMock,
            $this->configMock,
            $this->checkoutSessionMock,
            $this->orderPlaceMock
        );
    }

    public function testExecute()
    {
        $agreement = ['test-data'];

        $quoteMock = $this->getQuoteMock();
        $quoteMock->expects(self::once())
            ->method('getItemsCount')
            ->willReturn(1);

        $resultMock = $this->getResultMock();
        $resultMock->expects(self::once())
            ->method('setPath')
            ->with('checkout/onepage/success')
            ->willReturnSelf();

        $this->resultFactoryMock->expects(self::once())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($resultMock);

        $this->requestMock->expects(self::once())
            ->method('getPostValue')
            ->with('agreement', [])
            ->willReturn($agreement);

        $this->checkoutSessionMock->expects(self::once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $this->orderPlaceMock->expects(self::once())
            ->method('execute')
            ->with($quoteMock, [0]);

        $this->messageManagerMock->expects(self::never())
            ->method('addExceptionMessage');

        self::assertEquals($this->placeOrder->execute(), $resultMock);
    }

    public function testExecuteException()
    {
        $agreement = ['test-data'];

        $quote = $this->getQuoteMock();
        $quote->expects(self::once())
            ->method('getItemsCount')
            ->willReturn(0);

        $resultMock = $this->getResultMock();
        $resultMock->expects(self::once())
            ->method('setPath')
            ->with('checkout/cart')
            ->willReturnSelf();

        $this->resultFactoryMock->expects(self::once())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($resultMock);

        $this->requestMock->expects(self::once())
            ->method('getPostValue')
            ->with('agreement', [])
            ->willReturn($agreement);

        $this->checkoutSessionMock->expects(self::once())
            ->method('getQuote')
            ->willReturn($quote);

        $this->orderPlaceMock->expects(self::never())
            ->method('execute');

        $this->messageManagerMock->expects(self::once())
            ->method('addExceptionMessage')
            ->with(
                self::isInstanceOf('Exception'),
                'The order # cannot be processed.'
            );

        self::assertEquals($this->placeOrder->execute(), $resultMock);
    }

    /**
     * @return ResultInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getResultMock()
    {
        return $this->getMockBuilder(ResultInterface::class)
            ->setMethods(['setPath'])
            ->getMockForAbstractClass();
    }

    /**
     * @return Quote|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getQuoteMock()
    {
        return $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
