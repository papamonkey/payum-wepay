<?php
namespace Papamonkey\PayumWepay\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Papamonkey\PayumWepay\Api;

/**
 * TODO:这个应该是跟具体情况相关联的
 * sylius有sylius的，laravel有laravel的，这里先直接写成sylius的再做整改
*/
class ConvertPaymentAction implements ActionInterface, ApiAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }
    /**
     * {@inheritDoc}
     *
     * @param Convert $request
     */
    public function execute($request)
    {
        global $kernel;
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getSource();
        /** @var OrderInterface $order */
        /* $order = $payment->getOrder(); */    

        $oldDetails = ArrayObject::ensureArrayObject($payment->getDetails());
        if (!$oldDetails['prepay_id'] ) {
            $logger = $kernel->getContainer()->get('logger');
            $logger->info("on prepare");
            $details = ArrayObject::ensureArrayObject([
                'trade_type' => 'JSAPI',
                'body' => $payment->getNumber(),
                'detail' => $payment->getDescription(),
                'out_trade_no' => $payment->getNumber() . '123',
                'total_fee' => $payment->getTotalAmount(),
                'notify_url' => '/wxpay',
                'openid' => 'oCy7r0MUXbwcY4ncNQZwTwOk_qAU'
            ]);

            $result = $this->api->doPrepare((array)$details);
            if ($result->return_code != 'SUCCESS') {
                 throw new BadRequestHttpException($result->return_msg); 
            }
            $details['prepay_id'] = $result->prepay_id;
        } else {
            $details = $oldDetails;
        }
        $request->setResult((array) $details); //throw new \LogicException('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof PaymentInterface &&
            $request->getTo() == 'array'
        ;
    }
}
