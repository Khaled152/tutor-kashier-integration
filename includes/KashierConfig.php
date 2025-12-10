<?php
namespace TutorKashier\Includes;

use Tutor\Ecommerce\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KashierConfig {
    
    public function is_configured() {
        $settings = Settings::get_payment_gateway_settings( 'kashier' );
        
        $merchant_id = $this->get_value_from_settings( $settings, 'merchant_id' );
        $api_key     = $this->get_value_from_settings( $settings, 'api_key' );
        
        // Check for required fields
        if ( empty( $merchant_id ) || empty( $api_key ) ) {
            return false;
        }
        
        return true;
    }

    private function get_value_from_settings( $settings, $key ) {
        if ( ! isset( $settings['fields'] ) || ! is_array( $settings['fields'] ) ) {
            return '';
        }
        
        foreach ( $settings['fields'] as $field ) {
            if ( isset( $field['name'] ) && $field['name'] === $key ) {
                return isset( $field['value'] ) ? $field['value'] : '';
            }
        }
        return '';
    }
}
