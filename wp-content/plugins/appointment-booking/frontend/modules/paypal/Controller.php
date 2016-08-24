<?php
namespace Bookly\Frontend\Modules\Paypal;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Frontend\Modules\PayPal
 */
class Controller extends Lib\Base\Controller
{

    protected function getPermissions()
    {
        return array( '_this' => 'anonymous' );
    }

    public function expressCheckout()
    {
        $form_id = $this->getParameter( 'ab_fid' );
        if ( $form_id ) {
            // Create a paypal object.
            $paypal   = new Lib\Payment\PayPal();
            $userData = new Lib\UserBookingData( $form_id );

            if ( $userData->load() ) {
                $cart_info = $userData->cart->getInfo();
                $product = new \stdClass();
                $product->name  = $userData->cart->getItemsTitle( 126 );
                $product->price = $cart_info['total_deposit_price'];
                $product->qty   = 1;
                $paypal->addProduct( $product );

                // and send the payment request.
                try {
                    $paypal->send_EC_Request( $form_id );
                } catch ( \Exception $e ) {
                    $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_PAYPAL, 'error', $this->getParameter( 'error_msg' ) );
                    @wp_redirect( remove_query_arg( Lib\Payment\PayPal::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
                    exit;
                }
            }
        }
    }

    /**
     * Express Checkout 'CANCELURL' process
     */
    public function cancel()
    {
        $userData = new Lib\UserBookingData( $this->getParameter( 'ab_fid' ) );
        $userData->load();
        $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_PAYPAL, 'cancelled' );
        @wp_redirect( remove_query_arg( Lib\Payment\PayPal::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
        exit;
    }

    /**
     * Express Checkout 'ERRORURL' process
     */
    public function error()
    {
        $userData = new Lib\UserBookingData( $this->getParameter( 'ab_fid' ) );
        $userData->load();
        $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_PAYPAL, 'error', $this->getParameter( 'error_msg' ) );
        @wp_redirect( remove_query_arg( Lib\Payment\PayPal::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
        exit;
    }

    /**
     * Process the Express Checkout RETURNURL
     */
    public function success()
    {
        $form_id = $this->getParameter( 'ab_fid' );
        $PayPal  = new Lib\Payment\PayPal();
        $error_message = '';

        if ( $this->hasParameter( 'token' ) && $this->hasParameter( 'PayerID' ) ) {
            $token = $this->getParameter( 'token' );
            $data = array( 'TOKEN' => $token );
            // Send the request to PayPal.
            $response = $PayPal->sendNvpRequest( 'GetExpressCheckoutDetails', $data );

            if ( strtoupper( $response['ACK'] ) == 'SUCCESS' ) {
                $data['PAYERID'] = $this->getParameter( 'PayerID' );
                $data['PAYMENTREQUEST_0_PAYMENTACTION'] = 'Sale';

                foreach ( array( 'PAYMENTREQUEST_0_AMT', 'PAYMENTREQUEST_0_ITEMAMT', 'PAYMENTREQUEST_0_CURRENCYCODE', 'L_PAYMENTREQUEST_0' ) as $parameter ) {
                    if ( array_key_exists( $parameter, $response ) ) {
                        $data[ $parameter ] = $response[ $parameter ];
                    }
                }

                // We need to execute the "DoExpressCheckoutPayment" at this point to Receive payment from user.
                $response = $PayPal->sendNvpRequest( 'DoExpressCheckoutPayment', $data );
                if ( 'SUCCESS' == strtoupper( $response['ACK'] ) || 'SUCCESSWITHWARNING' == strtoupper( $response['ACK'] ) ) {
                    // Get transaction info
                    $response = $PayPal->sendNvpRequest( 'GetTransactionDetails', array( 'TRANSACTIONID' => $response['PAYMENTINFO_0_TRANSACTIONID'] ) );
                    if ( 'SUCCESS' == strtoupper( $response['ACK'] ) || 'SUCCESSWITHWARNING' == strtoupper( $response['ACK'] ) ) {
                        $userData = new Lib\UserBookingData( $form_id );
                        $userData->load();
                        $cart_info = $userData->cart->getInfo();
                        $payment = Lib\Entities\Payment::query()
                             ->select( 'id' )
                             ->where( 'type', Lib\Entities\Payment::TYPE_PAYPAL )
                             ->where( 'transaction_id', $response['TRANSACTIONID'] )
                             ->findOne();
                        if ( empty ( $payment ) ) {
                            $coupon = $userData->getCoupon();
                            if ( $coupon ) {
                                $coupon->claim();
                                $coupon->save();
                            }
                            $payment = new Lib\Entities\Payment();
                            $payment->set( 'transaction_id', $response['TRANSACTIONID'] )
                                ->set( 'type',    Lib\Entities\Payment::TYPE_PAYPAL )
                                ->set( 'status',  Lib\Entities\Payment::STATUS_COMPLETED )
                                ->set( 'total',   $cart_info['total_price'] )
                                ->set( 'token',   $token )
                                ->set( 'created', current_time( 'mysql' ) )
                                ->save();
                            $ca_list = $userData->save( $payment->get( 'id' ) );
                            Lib\NotificationSender::sendFromCart( $ca_list );
                            $payment->setDetails( $ca_list, $coupon )->save();
                        }
                        $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_PAYPAL, 'success' );

                        @wp_redirect( remove_query_arg( Lib\Payment\PayPal::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
                        exit;
                    } else {
                        $error_message = $response['L_LONGMESSAGE0'];
                    }
                } else {
                    $error_message = $response['L_LONGMESSAGE0'];
                }
            }
        } else {
            $error_message = __( 'Invalid token provided', 'bookly' );
        }

        if ( ! empty( $error_message ) ) {
            header( 'Location: ' . wp_sanitize_redirect( add_query_arg( array(
                    'action'    => 'ab-paypal-error',
                    'ab_fid'    => $form_id,
                    'error_msg' => str_replace( ' ', '%20', $error_message )
                ), Lib\Utils\Common::getCurrentPageURL()
                ) ) );
            exit;
        }
    }

}