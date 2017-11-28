<?php
namespace Papamonkey\PayumWepay\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Payum\Core\Exception\RequestNotSupportedException;
use Papamonkey\PayumWepay\Api;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CaptureAction implements ActionInterface, ApiAwareInterface
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
     * @param Capture $request
     */
    public function execute($request)
    {
        global $kernel;
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $logger = $kernel->getContainer()->get('logger');
        $logger->info("on execute" . json_encode((array)$model));
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
