<?php
/**
 * Plugin Name: Tutor LMS - Kashier Integration
 * Description: Integrates Kashier Payment Gateway with Tutor LMS Native Ecommerce.
 * Version: 1.0.0
 * Author: Antigravity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Defer loading until plugins are loaded to ensure Tutor classes are available
add_action( 'plugins_loaded', 'tutor_kashier_init', 99 );

function tutor_kashier_init() {
    // Check if Tutor LMS is active and GatewayBase is available
    if ( ! class_exists( 'Tutor\PaymentGateways\GatewayBase' ) ) {
        return;
    }

    // Autoload Includes
    require_once plugin_dir_path( __FILE__ ) . 'includes/KashierConfig.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/KashierGateway.php';  // Base Class
    require_once plugin_dir_path( __FILE__ ) . 'includes/KashierGateways.php'; // Children

    $gateways_list = [
        'kashier_card' => [
            'class' => \TutorKashier\Includes\KashierCardGateway::class,
            'config' => \TutorKashier\Includes\KashierCardConfig::class,
            'label' => __( 'Kashier Card', 'tutor-kashier' ),
            'desc'  => __( 'Pay using Credit/Debit Card', 'tutor-kashier' ),
        ],
        'kashier_wallet' => [
            'class' => \TutorKashier\Includes\KashierWalletGateway::class,
            'config' => \TutorKashier\Includes\KashierWalletConfig::class,
            'label' => __( 'Kashier Wallet', 'tutor-kashier' ),
            'desc'  => __( 'Pay using Mobile Wallet', 'tutor-kashier' ),
        ],
        'kashier_fawry' => [
            'class' => \TutorKashier\Includes\KashierFawryGateway::class,
            'config' => \TutorKashier\Includes\KashierFawryConfig::class,
            'label' => __( 'Kashier Fawry', 'tutor-kashier' ),
            'desc'  => __( 'Pay using Fawry Pay', 'tutor-kashier' ),
        ],
        'kashier_installments' => [
            'class' => \TutorKashier\Includes\KashierInstallmentsGateway::class,
            'config' => \TutorKashier\Includes\KashierInstallmentsConfig::class,
            'label' => __( 'Kashier Installments', 'tutor-kashier' ),
            'desc'  => __( 'Pay using Bank Installments', 'tutor-kashier' ),
        ],
        'kashier_valu' => [
            'class' => \TutorKashier\Includes\KashierValuGateway::class,
            'config' => \TutorKashier\Includes\KashierValuConfig::class,
            'label' => __( 'Kashier ValU', 'tutor-kashier' ),
            'desc'  => __( 'Buy Now Pay Later with ValU', 'tutor-kashier' ),
        ],
        'kashier_souhoola' => [
            'class' => \TutorKashier\Includes\KashierSouhoolaGateway::class,
            'config' => \TutorKashier\Includes\KashierSouhoolaConfig::class,
            'label' => __( 'Kashier Souhoola', 'tutor-kashier' ),
            'desc'  => __( 'Buy Now Pay Later with Souhoola', 'tutor-kashier' ),
        ],
        'kashier_aman' => [
            'class' => \TutorKashier\Includes\KashierAmanGateway::class,
            'config' => \TutorKashier\Includes\KashierAmanConfig::class,
            'label' => __( 'Kashier Aman', 'tutor-kashier' ),
            'desc'  => __( 'Buy Now Pay Later with Aman', 'tutor-kashier' ),
        ],
    ];

    foreach ( $gateways_list as $key => $data ) {
        
        // Register Gateway Classes
        add_filter( 'tutor_payment_gateways_with_class', function( $gateways ) use ($key, $data) {
            $gateways[$key] = [
                'gateway_class' => $data['class'],
                'config_class'  => $data['config'],
            ];
            return $gateways;
        });

        // Register Labels
        add_filter( 'tutor_payment_method_labels', function( $labels ) use ($key, $data) {
            $labels[$key] = $data['label'];
            return $labels;
        });

        // Register Settings Fields
        add_filter( 'tutor_payment_gateways', function( $gateways ) use ($key, $data) {
            
            $is_active = \Tutor\Ecommerce\Settings::is_active( $key );
            
            // Standard Fields for each gateway
            $fields = [
                [
                    'name'    => 'test_mode',
                    'label'   => __( 'Test Mode', 'tutor-kashier' ),
                    'type'    => 'select',
                    'options' => [
                        'yes' => 'Enable',
                        'no'  => 'Disable'
                    ],
                    'value'   => 'yes', 
                    'desc'    => __( 'Enable Test Mode to use sandbox credentials.', 'tutor-kashier' ),
                ],
                [
                    'name'  => 'merchant_id',
                    'type'  => 'text',
                    'label' => __( 'Merchant ID', 'tutor-kashier' ),
                    'desc'  => __( 'Your Kashier Merchant ID (MID).', 'tutor-kashier' ),
                ],
                [
                    'name'  => 'api_key',
                    'type'  => 'text',
                    'label' => __( 'API Key', 'tutor-kashier' ),
                    'desc'  => __( 'Your Kashier API Key.', 'tutor-kashier' ),
                ],
                [
                    'name'  => 'secret_key',
                    'type'  => 'text',
                    'label' => __( 'Secret Key', 'tutor-kashier' ),
                    'desc'  => __( 'Your Kashier Secret Key (optional).', 'tutor-kashier' ),
                ],
                [
                    'name'  => 'webhook_url_display',
                    'type'  => 'text',
                    'label' => __( 'Webhook / Return URL', 'tutor-kashier' ),
                    'value' => get_rest_url( null, 'tutor/v1/ecommerce-webhook/' . $key ),
                    'desc'  => __( 'Copy this URL to your Kashier Dashboard.', 'tutor-kashier' ),
                ]
            ];
        
            $gateways[] = [
                'name'             => $key,
                'label'            => $data['label'],
                'is_installed'     => true,
                'is_plugin_active' => true,
                'is_active'        => $is_active,
                'icon'             => '', 
                'fields'           => $fields,
            ];
        
            return $gateways;
        });
    }
}
