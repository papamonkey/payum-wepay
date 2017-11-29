<?php
namespace Papamonkey\PayumWepay;

use Http\Message\MessageFactory;
use Payum\Core\Exception\Http\HttpException;
use Payum\Core\HttpClientInterface;
use EasyWeChat\Foundation\Application;
use EasyWeChat\Payment\Order;
use Payum\Core\Bridge\Spl\ArrayObject;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Api
{
    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected $wechatOptions = [];

    protected $wechatApp;

    /**
     * @param array               $options
     * @param HttpClientInterface $client
     * @param MessageFactory      $messageFactory
     *
     * @throws \Payum\Core\Exception\InvalidArgumentException if an option is invalid
     */
    public function __construct(array $options, HttpClientInterface $client, MessageFactory $messageFactory)
    {
        $options = ArrayObject::ensureArrayObject($options);
        $options->defaults($this->options);
        $options->validateNotEmpty([
            'app_id',
            'merchant_id',
            'key',
            'type'
        ]);
        $this->options = $options;
        $this->client = $client;
        $this->messageFactory = $messageFactory;
        $this->wechatOptions = [
            'debug' => true,
            'app_id' => $options['app_id'],
            'payment' => [
                'merchant_id' => $options['merchant_id'],
                'key' => $options['key']
            ],
            'log' => [
                'level' => 'debug',
                'file' => '/tmp/wechat.log'
            ]
        ];
        $this->wechatApp = new Application($this->wechatOptions);
    }

    /**
     * eg.:
     *
		```
        $attributes = [
            'trade_type'       => 'JSAPI', // JSAPI，NATIVE，APP...
            'body'             => 'iPad mini 16G 白色',
            'detail'           => 'iPad mini 16G 白色',
            'out_trade_no'     => '1217752501201407033233368018',
            'total_fee'        => 5388, // 单位：分
            'notify_url'       => 'http://xxx.com/order-notify', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'sub_openid'        => '当前用户的 openid', // 如果传入sub_openid, 请在实例化Application时, 同时传入$sub_app_id, $sub_merchant_id
            // ...
        ]; 
		```
     * @param array $fields                                                               
     *  
     * @return array
     */ 
    public function doPrepare(array $fields)                                              
    {   
        if (!isset($fields['trade_type']))
            $fields['trade_type'] = $this->options['type'];
        $order = new Order($fields);
        $payment = $this->wechatApp->payment;
        $result = $payment->prepare($order);
        if ($result->return_code != 'SUCCESS') {
             throw new BadRequestHttpException($result->return_msg); 
        }
        switch ($fields['trade_type']) {
            case 'JSAPI':
                $config = $payment->configForJSSDKPayment($result->prepay_id);
                break;
            case 'APP':
                $config = $payment->configForAppPayment($result->prepay_id);
                break;
            default:
                throw  new \InvalidArgumentException("trade_type:{$fields['trade_type']} unsupported");
        }

        $config['timeStamp'] = $config['timestamp'];
        unset($config['timestamp']);
        return $config;
    }    

    public function doNotify(\Closure $callback)
    {
        $payment = $this->wechatApp->payment;
		$response = $payment->handleNotify(function($notify, $successful) use($payment) {
			//支付失败情况下，将Order的payment_status重置为ready，并发送模板通知给客户
			if ($notify->return_code != 'SUCCESS') {
				return true;
			}
			$queryResult = $payment->query($notify->out_trade_no);
			if ($queryResult->return_code != 'SUCCESS')
				return "no such order named " . $notify->out_trade_no;

            // 订单存在并且支付成功
            if (is_callable($callback)) {
                $callback();
            }
			return true; // 或者错误消息
        });
        return $response;
    }

    /**
     * @param array $fields
     *
     * @return array
     * @unused
     */
    protected function doRequest($method, array $fields)
    {
        $headers = [];

        $request = $this->messageFactory->createRequest($method, $this->getApiEndpoint(), $headers, http_build_query($fields));

        $response = $this->client->send($request);

        if (false == ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            throw HttpException::factory($request, $response);
        }

        return $response;
    }

    /**
     * @return string
     * @unused
     */
    protected function getApiEndpoint()
    {
        return $this->options['sandbox'] ? 'http://sandbox.example.com' : 'http://example.com';
    }

}
