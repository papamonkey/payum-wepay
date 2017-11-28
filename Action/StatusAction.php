<?php
namespace Papamonkey\PayumWepay\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Request\GetStatusInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;

class StatusAction implements ActionInterface
{
    const FIELD_STATUS = "status";
    /**
     * {@inheritDoc}
     *
     * @param GetStatusInterface $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (false == $model[static::FIELD_STATUS]) {
            $request->markNew();
            return;
        }
        if (static::STATUS_CAPTURED == $model[static::FIELD_STATUS]) {
            $request->markCaptured();

            return;
        }

        if (static::STATUS_CANCELED == $model[static::FIELD_STATUS]) {
            $request->markCanceled();

            return;
        }
        $request->markUnknown();
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
