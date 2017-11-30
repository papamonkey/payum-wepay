<?php
namespace Papamonkey\PayumWepay\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Notify;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Papamonkey\PayumWepay\Api;
use Payum\Core\Model\PaymentInterface;

class NotifyAction implements ActionInterface, ApiAwareInterface
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
     * @param Notify $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /* $details = ArrayObject::ensureArrayObject($request->getModel()); */

        $result = [];
        $response = $this->api->doNotify(function() use (&$request, &$result){
            $payment = $request->getModel();
            $details = ArrayObject::ensureArrayObject($payment->getDetails());
            $details['status'] = 'completed';
            $payment->setDetails($details);
        });
        $request->setModel($response);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Notify 
        ;
    }
}
