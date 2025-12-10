# Tutor LMS - Kashier Payment Integration

This WordPress plugin integrates **Kashier Payment Gateway** directly with **Tutor LMS**, allowing you to accept payments via Credit Card, Mobile Wallets, Fawry, and BNPL methods (ValU, Souhoola, Aman) natively within your course marketplace.

## Features

- **Direct Integration**: Extends Tutor LMS `GatewayBase` for seamless payment processing.
- **Modular Gateways**: Each payment method is registered separately, allowing you to enable/disable specific options:
    - Kashier Card (Credit/Debit)
    - Kashier Wallet (Mobile Wallets)
    - Kashier Fawry (Pay at Fawry)
    - Kashier Installments (Bank Installments)
    - Kashier ValU (BNPL)
    - Kashier Souhoola (BNPL)
    - Kashier Aman (BNPL)
- **Separate Configurations**: Configure Merchant ID and API Key independently for different methods if needed (or use the same credentials).
- **Test Mode Support**: Toggle between Live and Test (Sandbox) environments per gateway.
- **Automatic Order Status**: Updates Tutor LMS order status to "Completed" upon successful payment.

## Installation

1. Upload the `tutor-kashier-integration` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Ensure **Tutor LMS** (and Tutor LMS Pro if using paid features) is installed and active.

## Configuration

1. Go to **Tutor LMS > Settings > Monetization**.
2. Scroll to the **Payment Gateways** section.
3. You will see separate tabs for each Kashier method (e.g., *Kashier Card*, *Kashier Wallet*).
4. Enable the methods you wish to offer.
5. For each enabled method, enter your **Kashier Credentials**:
    - **Test Mode**: Enable for Sandbox testing, Disable for Live production.
    - **Merchant ID**: Your Kashier Merchant ID (MID).
    - **API Key**: Your Kashier API Key.
    - **Secret Key**: (Optional) Your Kashier Secret Key.
6. **Webhook URL**: Copy the displayed "Webhook / Return URL" and add it to your Kashier Dashboard if you need server-to-server notifications (though the redirection handles immediate order updates).
7. Save changes.

## Troubleshooting

- **"You are not allowed to make a payment"**:
    - Ensure your Tutor LMS currency matches the allowed currency for the method (e.g., EGP for Wallet/Fawry).
    - Check if "Test Mode" matches the credentials you entered.
    - Verify that the specific payment method is enabled on your Kashier Merchant Account.
- **Class not found errors**: Ensure Tutor LMS is active before activating this plugin.

## Requirements

- WordPress 5.0+
- Tutor LMS 2.0+
- PHP 7.4+
