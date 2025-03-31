<?php

namespace Drupal\viabill_payments\Helper;

use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\viabill_payments\Helper\ViaBillConstants;

/**
 * A helper class for various ViaBill utility methods.
 */
class ViaBillHelper {

    /**
     * The current API protocol version
     */
    private const API_PROTOCOL = '3.0';

    /**
     * The current API protocol version
     */
    private const API_PLATFORM = ViaBillConstants::AFFILIATE;

    /**
     * @var bool
     */
    protected $testMode;

    /**
     * @var string
     */
    protected $transactionType;

    /**
     * @var string
     */
    protected $apiSecret;

    /**
     * @var string
     */
    public $apiKey;

    /**
     * @var string
     */
    public $tbyb;

    /**
     * @var string
     */
    public $priceTagScript;     
   
    /**
     * Constructs a new ViaBillHelper.
     *
     * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
     *   The logger channel factory service.
     */
    public function __construct()
    {                                        
        // Retrieve gateway plugin + configuration.
        $configuration = \Drupal::config('commerce_payment.commerce_payment_gateway.viabill_payments')->get();
        
        $payment_gateway_storage = \Drupal::entityTypeManager()->getStorage('commerce_payment_gateway');
        // 'viabill_payments' must match the Payment Gateway entity ID, not just the plugin ID.
        $gateway_entity = $payment_gateway_storage->load('viabill_payments');
        if ($gateway_entity instanceof PaymentGatewayInterface) {
            $plugin = $gateway_entity->getPlugin();
            $mode = $plugin->getMode(); // 'test' or 'live'
            if ($mode === 'test') {
                $this->testMode = ViaBillConstants::TEST_MODE_ON; // Test mode logic
            } else {
                $this->testMode = ViaBillConstants::TEST_MODE_OFF; // Live mode logic
            }
        }                

        $this->tbyb = ViaBillConstants::TBYB_OFF;
        
        $payment_gateway_storage = \Drupal::entityTypeManager()->getStorage('commerce_payment_gateway');
        /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $gateway */
        $gateway_entity = $payment_gateway_storage->load('viabill_payments');
        if ($gateway_entity instanceof PaymentGatewayInterface) {
            $plugin = $gateway_entity->getPlugin();
            $configuration = $plugin->getConfiguration();
            if (!empty($configuration)) {
                $this->apiKey = $configuration['api_key'] ?? '';
                $this->apiSecret = $configuration['api_secret'] ?? '';
                $this->priceTagScript = $configuration['viabill_pricetag'] ?? '';                
                $this->transactionType = $configuration['transaction_type'];                                
            }            
        }                
    }        

    /**
     * Method to retrieve the test mode (true or false)
     **/ 
    public function getTestMode()
    {
        return $this->testMode;
    }

    /**
     * Method to retrieve the tbyb (Try Before You Buy/1 or 0)
     **/ 
    public function getTBYB()
    {
        return $this->tbyb;
    }

    /**
     * Method to retrieve the transaction type (authorize only, or authorize and capture)
     **/
    public function getTransactionType($transaction_id = null)
    {
        return $this->transactionType;
    }

    /**
     * Method to retrieve the ViaBill apiKey
     **/ 
    public function getAPIKey()
    {
        return $this->apiKey;
    }

    /**
     * Method to retrieve the ViaBill secret key
     **/ 
    public function getSecretKey()
    {
        return $this->apiSecret;
    }                     

    /**
     * Example helper to format a customer's address or name, etc.
     *
     * @param array $customerData
     *   An array containing the customer's data.
     *
     * @return string
     *   JSON-encoded string for the gateway, for instance.
     */
    public function buildCustomerInfoJson(array $customerData) {
        return json_encode($customerData);
    }

    /**
     * Example helper to format a customer's address or name, etc.
     *
     * @param array $customerData
     *   An array containing the customer's data.
     *
     * @return string
     *   JSON-encoded string for the gateway, for instance.
     */
    public function buildCartInfoJson(array $cartData) {
        return json_encode($cartData);
    }
    
    public function formatTransactionId($order_id) {
        $transaction_id = 'vb-'.$order_id.'-'.$this->generateRandomString();
        return $transaction_id;
    }
    
    public function formatAmount($amount, $return_numeric = true) {
        $formatted_number = $amount;
        if (is_numeric($amount)) {
            $formatted_number = number_format($amount, 2, '.', '');
        } else {
            $formatted_number = '0';
        }
        return $formatted_number;
    }

    public function formatTBYB($tbyb) {
        if (empty($tbyb)) {
            return ViaBillConstants::TBYB_OFF;
        } else if (($tbyb == 'true')||($tbyb == '1')||($tbyb == 1)) {
            return ViaBillConstants::TBYB_ON;
        } else return ViaBillConstants::TBYB_OFF;        
    }

    public function getFormattedTBYB() {
        return $this->formatTBYB($this->getTBYB());
    }

    public function formatTestMode($mode) {
        if (empty($mode)) {            
            return ViaBillConstants::TEST_MODE_ON;
        } else if (($mode == 'test')||($mode == 'true')||($mode == '1')||($mode == 1)) {
            return ViaBillConstants::TEST_MODE_ON;
        } else return ViaBillConstants::TEST_MODE_OFF;
    }    
    
    public function getFormattedTestMode() {
        return $this->formatTestMode($this->getTestMode());
    }

    public function getViaBillApiPlatform() {
        return self::API_PLATFORM;
    }

    public function generateRandomString($length = 10) {
        // Define the characters you want to include in the random string
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        
        // Loop for the number of characters needed
        for ($i = 0; $i < $length; $i++) {
            // Use random_int for better randomness and security
            $index = random_int(0, $charactersLength - 1);
            $randomString .= $characters[$index];
        }
        
        return $randomString;
    }    

    public function log($message, $level = 'info') {
        switch ($level) {
          case 'info':
            \Drupal::logger('viabill_payments')->info($message);
            break;
      
          case 'error':
            \Drupal::logger('viabill_payments')->error($message);
            break;
        }
    }
  
}
