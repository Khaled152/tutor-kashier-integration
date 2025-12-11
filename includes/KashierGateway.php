<?php
/**
 * Kashier Payment Gateway for Tutor LMS
 *
 * @package TutorKashier
 */

namespace TutorKashier\Includes;

use Tutor\PaymentGateways\GatewayBase;
use Tutor\Ecommerce\Settings;
use Tutor\Ecommerce\Ecommerce;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class KashierBaseGateway extends GatewayBase {

    protected $gateway_key = 'kashier'; // will be overridden
    protected $method_label = 'Kashier';

    public function get_root_dir_name(): string {
        return 'Kashier';
    }

    public function get_payment_class(): string {
        return '';
    }

    // Child classes must define their own config class or share one if adapted
    public function get_config_class(): string {
        // We will assume a naming convention or separate config classes
        return str_replace( 'Gateway', 'Config', static::class );
    }
    
    // Abstract method to get the specific Kashier allowedMethods string (e.g. 'card', 'bnpl[valu]')
    abstract protected function get_kashier_method();

    public function __construct() {
        // Child class should set $this->gateway_key
    }

    /**
     * Get settings value
     */
    protected function get_setting( $key, $default = '' ) {
        $settings = Settings::get_payment_gateway_settings( $this->gateway_key );
        
        if ( ! isset( $settings['fields'] ) || ! is_array( $settings['fields'] ) ) {
            return $default;
        }

        foreach ( $settings['fields'] as $field ) {
            if ( isset( $field['name'] ) && $field['name'] === $key ) {
                return isset( $field['value'] ) ? $field['value'] : $default;
            }
        }

        return $default;
    }

    /**
     * Generate HMAC Signature
     */
    private function generate_hash( $merchantId, $orderId, $amount, $currency, $apiKey ) {
        $path = '/?payment=' . $merchantId . '.' . $orderId . '.' . $amount . '.' . $currency;
        return hash_hmac( 'sha256', $path, $apiKey, false );
    }

    /**
     * Setup Payment and Redirect
     */
    public function setup_payment_and_redirect( $data ) {
        $merchantId = $this->get_setting( 'merchant_id' );
        $apiKey     = $this->get_setting( 'api_key' );
        
        $testMode   = $this->get_setting( 'test_mode' ) === 'yes';
        
        if ( empty( $merchantId ) || empty( $apiKey ) ) {
            wp_die( $this->method_label . ' is not configured properly.' );
        }

        // Handle $data object/array normalization
        $order_id = 0;
        if ( is_object( $data ) ) {
            $order_id = $data->order_id ?? 0;
        } elseif ( is_array( $data ) ) {
            $order_id = $data['order_id'] ?? 0;
        }

        if ( ! $order_id && isset( $data->id ) ) {
             $order_id = $data->id;
        }

        // Amount
        $amount = 0;
        if ( is_object( $data ) ) {
            $amount = $data->total_price ?? $data->total_amount ?? 0;
        } elseif ( is_array( $data ) ) {
            $amount = $data['total_price'] ?? $data['total_amount'] ?? 0;
        }

        // Currency
        $currency = 'EGP';
        if ( is_object( $data ) && isset( $data->currency ) ) {
            if ( is_object( $data->currency ) && isset( $data->currency->code ) ) {
                $currency = $data->currency->code;
            } elseif ( is_string( $data->currency ) ) {
                $currency = $data->currency;
            }
        } elseif ( is_array( $data ) && isset( $data['currency'] ) ) {
             $c = $data['currency'];
             $currency = is_array($c) ? ($c['code'] ?? 'EGP') : $c;
        }

        $amount = number_format( (float) $amount, 2, '.', '' );
        $currency = strtoupper( $currency );
        
        // Prepare Order ID
        // Note: Using gateway_key in order ID prefix might help distinguish logistically, 
        // but typically standard ID + timestamp is enough
        $kashierOrderId = $order_id . '-' . time();

        $hash = $this->generate_hash( $merchantId, $kashierOrderId, $amount, $currency, $apiKey );

        $baseUrl = 'https://payments.kashier.io';
        $mode = $testMode ? 'test' : 'live';
        
        // Metadata
        $customerEmail = '';
        $customerName  = '';
        
        if ( is_object( $data ) && isset( $data->customer ) ) {
             $customerEmail = $data->customer->email ?? '';
             $customerName  = $data->customer->name ?? '';
        } elseif ( is_array( $data ) && isset( $data['customer'] ) ) {
             $c = $data['customer'];
             if ( is_object( $c ) ) {
                 $customerEmail = $c->email ?? '';
                 $customerName  = $c->name ?? '';
             } elseif ( is_array( $c ) ) {
                 $customerEmail = $c['email'] ?? '';
                 $customerName  = $c['name'] ?? '';
             }
        }

        $metaData = [
            'ecommercePlatform' => 'TutorLMS',
            'OrderId'           => $order_id,
            'CustomerEmail'     => $customerEmail,
            'CustomerName'      => $customerName,
        ];

        // Webhook URL (used as return URL too)
        $callbackUrl = get_rest_url( null, 'tutor/v1/ecommerce-webhook/' . $this->gateway_key );

        // Encode metadata
        $encodedMetaData = rawurlencode( json_encode( $metaData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );

        // Specific Method
        $allowedMethodsStr = $this->get_kashier_method();

        $queryParams = [
            'merchantId'       => $merchantId,
            'orderId'          => $kashierOrderId,
            'amount'           => $amount,
            'currency'         => $currency,
            'hash'             => $hash,
            'mode'             => $mode,
            'metaData'         => $encodedMetaData,
            'merchantRedirect' => $callbackUrl,
            'serverWebhook'    => $callbackUrl,
            'failureRedirect'  => 'true',
            'redirectMethod'   => 'get',
            'display'          => 'en',
        ];

        // Only add if not default/all
        if ( ! empty( $allowedMethodsStr ) ) {
            $queryParams['allowedMethods'] = $allowedMethodsStr;
            
            // Match Standalone: Add defaultMethod for BNPL
            // We can detect if allowedMethodsStr starts with 'bnpl'
            if ( strpos( $allowedMethodsStr, 'bnpl' ) === 0 ) {
                $queryParams['defaultMethod'] = $allowedMethodsStr;
            }
        }

        $redirectUrl = $baseUrl . '?' . http_build_query( $queryParams );

        wp_redirect( $redirectUrl );
        exit;
    }

    /**
     * Validate Signature
     */
    public function verify_webhook_signature( $webhook_data ) {
        $params = $webhook_data->get; 
        if ( empty( $params ) ) {
            $params = $webhook_data->post;
        }

        if ( empty( $params['paymentStatus'] ) && ! empty( $webhook_data->stream ) ) {
            $json = json_decode( $webhook_data->stream, true );
            if ( $json ) {
                $params = $json;
                if ( isset( $params['data'] ) ) {
                    $params = $params['data'];
                }
            }
        }
        
        $merchantOrderId = $params['merchantOrderId'] ?? $params['orderId'] ?? '';
        $orderIdparts = explode( '-', $merchantOrderId );
        $tutorOrderId = $orderIdparts[0];
        
        $status = strtolower( $params['paymentStatus'] ?? $params['status'] ?? '' );
        
        $success = ( $status === 'success' || $status === 'paid' );
        
        $result = new \stdClass();
        $result->id = $tutorOrderId;
        $result->transaction_id = $params['transactionId'] ?? '';
        $result->payment_method = $this->gateway_key;
        $result->payment_payload = $params;
        $result->earnings = 0;
        $result->fees = 0;
        
        if ( $success ) {
            $result->payment_status = 'paid';
        } else {
            $result->payment_status = 'failed';
        }

        // Determining redirect URL for GET requests
        if ( $_SERVER['REQUEST_METHOD'] === 'GET' ) {
            $checkout_page_id = (int) tutor_utils()->get_option( \Tutor\Ecommerce\CheckoutController::PAGE_ID_OPTION_NAME );
            $url = get_permalink( $checkout_page_id );
            
            $query_args = [
                'tutor_order_placement' => $success ? 'success' : 'failed',
                'payment_method' => $this->gateway_key,
                'order_id' => $tutorOrderId
            ];
            
            $result->redirectUrl = add_query_arg( $query_args, $url );
        }
        
        return $result;
    }

    /**
     * Make recurring payment (Subscription support)
     * 
     * For Kashier, we don't have tokenization, so this will redirect
     * the user to make a manual payment for subscription renewal.
     * 
     * @param int $order_id Order ID for recurring payment
     * @throws \Exception If payment setup fails
     * @return void
     */
    public function make_recurring_payment( int $order_id ) {
        // Validate order ID
        if ( ! $order_id ) {
            throw new \InvalidArgumentException( 'Invalid order ID for recurring payment.' );
        }

        try {
            // Prepare recurring payment data using Tutor's method
            $payment_data = \Tutor\Ecommerce\CheckoutController::prepare_recurring_payment_data( $order_id );

            if ( ! $payment_data ) {
                throw new \RuntimeException( 'Failed to prepare recurring payment data for order: ' . $order_id );
            }

            // Use the same payment flow as regular payments
            $this->setup_payment_and_redirect( $payment_data );
            
        } catch ( \Throwable $th ) {
            // Log error and rethrow
            error_log( 'Kashier recurring payment error for order ' . $order_id . ': ' . $th->getMessage() );
            throw $th;
        }
    }
}
