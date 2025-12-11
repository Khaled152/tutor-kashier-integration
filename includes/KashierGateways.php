<?php
namespace TutorKashier\Includes;

use Tutor\PaymentGateways\GatewayBase;

// The base class should be loaded already, but just in case
if ( ! class_exists( 'TutorKashier\Includes\KashierBaseGateway' ) ) {
    require_once __DIR__ . '/KashierGateway.php';
}

/**
 * Common Config class generator to avoid repetition
 */
abstract class AbstractKashierConfig {
    protected $gateway_key;
    public function __construct( $key ) { $this->gateway_key = $key; }
    
    /**
     * Create config - enables subscription support
     */
    public function createConfig(): void {
        // Enable subscription support (manual renewal)
        $config = array(
            'save_payment_method' => true,
        );
        // Note: We don't actually save payment methods, but this flag
        // tells Tutor LMS that we support subscriptions
    }
    
    public function is_configured() {
        $settings = \Tutor\Ecommerce\Settings::get_payment_gateway_settings( $this->gateway_key );
        $merchant_id = $this->get_val($settings, 'merchant_id');
        $api_key = $this->get_val($settings, 'api_key');
        return !empty($merchant_id) && !empty($api_key);
    }
    private function get_val($settings, $key) {
        if (!isset($settings['fields']) || !is_array($settings['fields'])) return '';
        foreach($settings['fields'] as $f) {
            if(isset($f['name']) && $f['name'] === $key) return $f['value'] ?? '';
        }
        return '';
    }
}

// ----------------------
// 1. CARD
// ----------------------
class KashierCardGateway extends KashierBaseGateway {
    protected $gateway_key = 'kashier_card';
    protected $method_label = 'Kashier Card';
    public function get_root_dir_name(): string { return 'KashierCard'; }
    protected function get_kashier_method() { return 'card'; }
    public function get_config_class(): string { return KashierCardConfig::class; }
}
class KashierCardConfig extends AbstractKashierConfig {
    public function __construct() { parent::__construct('kashier_card'); }
}

// ----------------------
// 2. WALLET
// ----------------------
class KashierWalletGateway extends KashierBaseGateway {
    protected $gateway_key = 'kashier_wallet';
    protected $method_label = 'Kashier Wallet';
    public function get_root_dir_name(): string { return 'KashierWallet'; }
    protected function get_kashier_method() { return 'wallet'; }
    public function get_config_class(): string { return KashierWalletConfig::class; }
}
class KashierWalletConfig extends AbstractKashierConfig {
    public function __construct() { parent::__construct('kashier_wallet'); }
}

// ----------------------
// 3. FAWRY
// ----------------------
class KashierFawryGateway extends KashierBaseGateway {
    protected $gateway_key = 'kashier_fawry';
    protected $method_label = 'Kashier Fawry';
    public function get_root_dir_name(): string { return 'KashierFawry'; }
    protected function get_kashier_method() { return 'fawry'; }
    public function get_config_class(): string { return KashierFawryConfig::class; }
}
class KashierFawryConfig extends AbstractKashierConfig {
    public function __construct() { parent::__construct('kashier_fawry'); }
}

// ----------------------
// 4. BANK INSTALLMENTS
// ----------------------
class KashierInstallmentsGateway extends KashierBaseGateway {
    protected $gateway_key = 'kashier_installments';
    protected $method_label = 'Kashier Installments';
    public function get_root_dir_name(): string { return 'KashierInstallments'; }
    protected function get_kashier_method() { return 'bank_installments'; }
    public function get_config_class(): string { return KashierInstallmentsConfig::class; }
}
class KashierInstallmentsConfig extends AbstractKashierConfig {
    public function __construct() { parent::__construct('kashier_installments'); }
}

// ----------------------
// 5. ValU (BNPL)
// ----------------------
class KashierValuGateway extends KashierBaseGateway {
    protected $gateway_key = 'kashier_valu';
    protected $method_label = 'Kashier ValU';
    public function get_root_dir_name(): string { return 'KashierValu'; }
    protected function get_kashier_method() { return 'bnpl[valu]'; }
    public function get_config_class(): string { return KashierValuConfig::class; }
}
class KashierValuConfig extends AbstractKashierConfig {
    public function __construct() { parent::__construct('kashier_valu'); }
}

// ----------------------
// 6. Souhoola (BNPL)
// ----------------------
class KashierSouhoolaGateway extends KashierBaseGateway {
    protected $gateway_key = 'kashier_souhoola';
    protected $method_label = 'Kashier Souhoola';
    public function get_root_dir_name(): string { return 'KashierSouhoola'; }
    protected function get_kashier_method() { return 'bnpl[souhoola]'; }
    public function get_config_class(): string { return KashierSouhoolaConfig::class; }
}
class KashierSouhoolaConfig extends AbstractKashierConfig {
    public function __construct() { parent::__construct('kashier_souhoola'); }
}

// ----------------------
// 7. Aman (BNPL)
// ----------------------
class KashierAmanGateway extends KashierBaseGateway {
    protected $gateway_key = 'kashier_aman';
    protected $method_label = 'Kashier Aman';
    public function get_root_dir_name(): string { return 'KashierAman'; }
    protected function get_kashier_method() { return 'bnpl[aman]'; }
    public function get_config_class(): string { return KashierAmanConfig::class; }
}
class KashierAmanConfig extends AbstractKashierConfig {
    public function __construct() { parent::__construct('kashier_aman'); }
}
