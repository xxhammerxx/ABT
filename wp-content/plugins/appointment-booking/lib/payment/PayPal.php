<?php
namespace Bookly\Lib\Payment;

use Bookly\Lib;

/**
 * Class PayPal
 * @package Bookly\Lib\Payment
 */
class PayPal
{
    // Array for cleaning PayPal request
    static public $remove_parameters = array( 'action', 'token', 'PayerID', 'ab_fid', 'error_msg', 'type' );

    /**
     * The array of products for checkout
     *
     * @var array
     */
    protected $products = array();

    /**
     * Send the Express Checkout NVP request
     *
     * @param $form_id
     * @throws \Exception
     */
    public function send_EC_Request( $form_id )
    {
        if ( !session_id() ) {
            @session_start();
        }

        if ( ! count( $this->products ) ) {
            throw new \Exception( 'Products not found!' );
        }

        $total = 0;

        // create the data to send on PayPal
        $data = array(
            'SOLUTIONTYPE' => 'Sole',
            'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
            'PAYMENTREQUEST_0_CURRENCYCODE'  => get_option( 'ab_currency' ),
            'NOSHIPPING' => 1,
            'RETURNURL'  => add_query_arg( array( 'action' => 'ab-paypal-return', 'ab_fid' => $form_id ), Lib\Utils\Common::getCurrentPageURL() ),
            'CANCELURL'  => add_query_arg( array( 'action' => 'ab-paypal-cancel', 'ab_fid' => $form_id ), Lib\Utils\Common::getCurrentPageURL() )
        );

        foreach ( $this->products as $index => $product ) {
            $data[ 'L_PAYMENTREQUEST_0_NAME' . $index ] = $product->name;
            $data[ 'L_PAYMENTREQUEST_0_AMT' . $index ]  = $product->price;
            $data[ 'L_PAYMENTREQUEST_0_QTY' . $index ]  = $product->qty;

            $total += ( $product->qty * $product->price );
        }
        $data['PAYMENTREQUEST_0_AMT']     = $total;
        $data['PAYMENTREQUEST_0_ITEMAMT'] = $total;

        // send the request to PayPal
        $response = self::sendNvpRequest( 'SetExpressCheckout', $data );

        // Respond according to message we receive from PayPal
        if ( 'SUCCESS' == strtoupper( $response['ACK'] ) || 'SUCCESSWITHWARNING' == strtoupper( $response['ACK'] ) ) {
            $paypalurl = 'https://www' . get_option( 'ab_paypal_ec_mode' ) . '.paypal.com/cgi-bin/webscr?cmd=_express-checkout&useraction=commit&token=' . urldecode( $response['TOKEN'] );
            header( 'Location: ' . $paypalurl );
            exit;
        } else {
            header( 'Location: ' . wp_sanitize_redirect( add_query_arg( array( 'action' => 'ab-paypal-error', 'ab_fid' => $form_id, 'error_msg' => str_replace( ' ', '%20', $response['L_LONGMESSAGE0'] ) ), Lib\Utils\Common::getCurrentPageURL() ) ) );
            exit;
        }
    }

    /**
     * Send the NVP Request to the PayPal
     *
     * @param       $method
     * @param array $data
     * @return array
     */
    public function sendNvpRequest( $method, array $data )
    {
        $url  = 'https://api-3t' . get_option( 'ab_paypal_ec_mode' ) . '.paypal.com/nvp';
        $curl = new Lib\Curl\Curl();
        $curl->options['CURLOPT_SSL_VERIFYPEER'] = false;
        $curl->options['CURLOPT_SSL_VERIFYHOST'] = false;

        $data['METHOD']    = $method;
        $data['VERSION']   = '76.0';
        $data['USER']      = get_option( 'ab_paypal_api_username' );
        $data['PWD']       = get_option( 'ab_paypal_api_password' );
        $data['SIGNATURE'] = get_option( 'ab_paypal_api_signature' );

        $httpResponse = $curl->post( $url, $data );
        if ( ! $httpResponse ) {
            exit( $curl->error() );
        }

        // Extract the response details.
        parse_str( $httpResponse, $PayPalResponse );

        if ( ! array_key_exists( 'ACK', $PayPalResponse ) ) {
            exit( 'Invalid HTTP Response for POST request to ' . $url );
        }

        return $PayPalResponse;
    }

    public static function renderForm( $form_id )
    {
        $html = '<form method="post" class="ab-paypal-form">';
        $html .= '<input type="hidden" name="action" value="ab-paypal-checkout"/>';
        $html .= '<input type="hidden" name="ab_fid" value="' . $form_id . '"/>';
        $html .= '<button class="ab-left ab-back-step ab-btn ladda-button" data-style="zoom-in" data-spinner-size="40"><span class="ladda-label">' . Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_button_back' ) . '</span></button>';
        $html .= '<button class="ab-right ab-next-step ab-btn ladda-button" data-style="zoom-in" data-spinner-size="40"><span class="ladda-label">' . Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_button_next' ) . '</span></button>';
        $html .= '</form>';

        echo $html;
    }

    /**
     * @return array
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Add the Product for payment
     *
     * @param \stdClass $product
     */
    public function addProduct( \stdClass $product )
    {
        $this->products[] = $product;
    }

}