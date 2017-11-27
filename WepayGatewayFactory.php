<?php
namespace Papamk\PayumWepay;

use Papamk\PayumWepay\Action\AuthorizeAction;
use Papamk\PayumWepay\Action\CancelAction;
use Papamk\PayumWepay\Action\ConvertPaymentAction;
use Papamk\PayumWepay\Action\CaptureAction;
use Papamk\PayumWepay\Action\NotifyAction;
use Papamk\PayumWepay\Action\RefundAction;
use Papamk\PayumWepay\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class WepayGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name' => 'wepay',
            'payum.factory_title' => 'Wechat Payment',
            'payum.action.capture' => new CaptureAction(),
            'payum.action.authorize' => new AuthorizeAction(),
            'payum.action.refund' => new RefundAction(),
            'payum.action.cancel' => new CancelAction(),
            'payum.action.notify' => new NotifyAction(),
            'payum.action.status' => new StatusAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = array(
                'sandbox' => false, // wepay not support sandbox
            );
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = [];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api((array) $config, $config['payum.http_client'], $config['httplug.message_factory']);
            };
        }
    }
}
