<?php
/**
 * Copyright © 2018 Appmerce - Applications for Ecommerce
 * http://www.appmerce.com
 */

include (DIR_SYSTEM . 'storage/vendor/rabobank/vendor/autoload.php');

use nl\rabobank\gict\payments_savings\omnikassa_sdk\model\Money;
use nl\rabobank\gict\payments_savings\omnikassa_sdk\model\OrderItem;
use nl\rabobank\gict\payments_savings\omnikassa_sdk\model\PaymentBrand;
use nl\rabobank\gict\payments_savings\omnikassa_sdk\model\PaymentBrandForce;
use nl\rabobank\gict\payments_savings\omnikassa_sdk\model\ProductType;
use nl\rabobank\gict\payments_savings\omnikassa_sdk\model\request\MerchantOrder;
use nl\rabobank\gict\payments_savings\omnikassa_sdk\model\Address;
use nl\rabobank\gict\payments_savings\omnikassa_sdk\model\CustomerInformation;
use nl\rabobank\gict\payments_savings\omnikassa_sdk\model\VatCategory;
use nl\rabobank\gict\payments_savings\omnikassa_sdk\endpoint\Endpoint;
use nl\rabobank\gict\payments_savings\omnikassa_sdk\model\signing\SigningKey;
use nl\rabobank\gict\payments_savings\omnikassa_sdk\connector\TokenProvider;
use nl\rabobank\gict\payments_savings\omnikassa_sdk\model\response\PaymentCompletedResponse;
use nl\rabobank\gict\payments_savings\omnikassa_sdk\model\response\AnnouncementResponse;

class InMemoryTokenProvider extends TokenProvider
{
    private $map = array();
    
    /**
    * Construct the in memory token provider with the given refresh token.
    * @param string $refreshToken The refresh token used to retrieve the
    access tokens with.
    */
    public function __construct($refreshToken)
    {
        $this->setValue('REFRESH_TOKEN', $refreshToken);
    }

    /**
    * Retrieve the value for the given key.
    *
    * @param string $key
    * @return string Value of the given key or null if it does not exists.
    */
    protected function getValue($key)
    {
        return array_key_exists($key, $this->map) ? $this->map[$key] :
        null;
    }
    
    /**
    * Store the value by the given key.
    *
    * @param string $key
    * @param string $value
    */
    protected function setValue($key, $value)
    {
        $this->map[$key] = $value;
    }
    
    /**
    * Optional functionality to flush your systems.
    * It is called after storing all the values of the access token and
    can be used for example to clean caches or reload changes from the
    database.
    */
    protected function flush()
    {
    }
}

class ControllerPaymentOmnikassa extends Controller
{
    public function index()
    {
        $this->load->model('checkout/order');
        $this->load->language('payment/omnikassa');
        $data['button_confirm'] = $this->language->get('button_confirm');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $merchantOrderId = $this->session->data['order_id'];
        $description = substr(($this->session->data['order_id'] . ' ' . $order_info['payment_lastname']), 0, 35);
        $amount = Money::fromCents($order_info['currency_code'], $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) * 100);
        $language = $this->session->data['language'];
        $merchantReturnUrl = $this->url->link('payment/omnikassa/returnuser');
        
        $merchantOrder = new MerchantOrder(
            $merchantOrderId, 
            $description, 
            FALSE, 
            $amount, 
            FALSE, 
            $language, 
            $merchantReturnUrl
        );
        
        if ($this->config->get('omnikassa_test_mode')) {
            $rokServerUrl = 'https://betalen.rabobank.nl/omnikassa-api-sandbox/';
        }
        else {
            $rokServerUrl = 'https://betalen.rabobank.nl/omnikassa-api/';
        }
        $signingKey = $this->config->get('omnikassa_signing_key');
        $signingKey = new SigningKey(base64_decode($signingKey));
        $refreshToken = $this->config->get('omnikassa_refresh_token');
        $inMemoryTokenProvider = new InMemoryTokenProvider($refreshToken);
        $endpoint = Endpoint::createInstance($rokServerUrl, $signingKey, $inMemoryTokenProvider);
        $redirectUrl = $endpoint->announceMerchantOrder($merchantOrder);
        $data['action'] = $redirectUrl;
        
        // Add hidden token var
        $parse_url = parse_url($redirectUrl);
        parse_str($parse_url['query'], $parse_query);
        $data['token'] = $parse_query['token'];

		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/omnikassa.tpl')) {
			return $this->load->view($this->config->get('config_template') . '/template/payment/omnikassa.tpl', $data);
		} else {
			return $this->load->view('default/template/payment/omnikassa.tpl', $data);
		}
    }

    public function returnuser() {
        $params = $this->request->post;
        
        if (isset($params['order_id']) && isset($params['status'])) {
            $signingKey = $this->config->get('omnikassa_signing_key');
            $signingKey = new SigningKey(base64_decode($signingKey));
            $paymentCompletedResponse = PaymentCompletedResponse::createInstance($params['order_id'], $params['status'], $params['signature'], $signingKey);
            
            if (!$paymentCompletedResponse) {
                $this->failure();
            }
            else {
                $validatedStatus = $paymentCompletedResponse->getStatus();

                switch ($validatedStatus) {
                    case 'COMPLETED':
                    case 'IN_PROGRESS':
                        $this->success();
                        break;
                        
                    case 'EXPIRED':
                    case 'CANCELLED':
                    default:
                        $this->failure();
                }
            }
        }
        else {
            $this->failure();
        }
    }
    
    public function redirect($url, $status = 302) {
        header('Location: ' . str_replace(array('&amp;', "\n", "\r"), array('&', '', ''), $url), true, $status);
        exit();
    }
    
    public function success()
    {
        $this->redirect($this->url->link('checkout/success'));
    }

    public function failure()
    {
        $this->redirect($this->url->link('checkout/checkout', '', 'SSL'));
    }
    
    public function webhook() {
        $json = file_get_contents('php://input'); 

        $signingKey = $this->config->get('omnikassa_signing_key');
        $signingKey = new SigningKey(base64_decode($signingKey));

        if ($json) {
            $announcementResponse = new AnnouncementResponse($json, $signingKey);
            if ($this->config->get('omnikassa_test_mode')) {
                $rokServerUrl = 'https://betalen.rabobank.nl/omnikassa-api/';
            }
            else {
                $rokServerUrl = 'https://betalen.rabobank.nl/omnikassa-api-sandbox/';
            }
            $refreshToken = $this->config->get('omnikassa_refresh_token');
            $inMemoryTokenProvider = new InMemoryTokenProvider($refreshToken);
            $endpoint = Endpoint::createInstance($rokServerUrl, $signingKey, $inMemoryTokenProvider);
            do {
                $response = $endpoint->retrieveAnnouncement($announcementResponse);
                $responseResults = $response->getOrderResults();
                
                // Cycle through multiple notifications
                foreach ($responseResults as $paydata) {
                    $order_id = $paydata->getMerchantOrderId();
                    switch ($paydata->getOrderStatus()) {
                        case 'COMPLETED' :
                            $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('omnikassa_order_status_id'), '', true);
                            break;

                        case 'IN_PROGRESS' :
                            $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('omnikassa_pending_status_id'), '', true);
                            break;

                        case 'CANCELLED' :
                            $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('omnikassa_canceled_status_id'), '', true);
                            break;
                            
                        case 'EXPIRED' :
                        default :
                            $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('omnikassa_failed_status_id'), '', true);
                    }   
                }
            } while ($response->isMoreOrderResultsAvailable());

            // Return HTTP OK
            http_response_code(200);
        }
    }
    
}
