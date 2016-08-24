<?php
namespace Bookly\Frontend\Modules\TwoCheckout;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Frontend\Modules\TwoCheckout
 */
class Controller extends Lib\Base\Controller
{

    protected function getPermissions()
    {
        return array( '_this' => 'anonymous' );
    }

    public function approved()
    {
        $userData = new Lib\UserBookingData( $this->getParameter( 'ab_fid' ) );
        if ( ( $redirect = $this->getParameter( 'x_receipt_link_url', false ) ) === false ) {
            // Clean GET parameters from 2Checkout.
            $redirect = remove_query_arg( Lib\Payment\TwoCheckout::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() );
        }
        if ( $userData->load() ) {
            $cart_info = $userData->cart->getInfo();
            $total = number_format( $cart_info['total_deposit_price'], 2, '.', '' );
            $StringToHash = strtoupper( md5( get_option( 'ab_2checkout_api_secret_word' ) . get_option( 'ab_2checkout_api_seller_id' ) . $this->getParameter( 'order_number' ) . $total ) );
            if ( $StringToHash != $this->getParameter( 'key' ) ) {
                header( 'Location: ' . wp_sanitize_redirect( add_query_arg( array(
                        'action'    => 'ab-2checkout-error',
                        'ab_fid'    => $this->getParameter( 'ab_fid' ),
                        'error_msg' => str_replace( ' ', '%20', __( 'Invalid token provided', 'bookly' ) )
                    ), Lib\Utils\Common::getCurrentPageURL()
                ) ) );
                exit;
            } else {
                $transaction_id = $this->getParameter( 'order_number' );
                $payment = Lib\Entities\Payment::query()
                    ->select( 'id' )
                    ->where( 'type', Lib\Entities\Payment::TYPE_2CHECKOUT )
                    ->where( 'transaction_id', $transaction_id )
                    ->findOne();
                if ( empty ( $payment ) ) {
                    $coupon = $userData->getCoupon();
                    if ( $coupon ) {
                        $coupon->claim();
                        $coupon->save();
                    }
                    $payment = new Lib\Entities\Payment();
                    $payment->set( 'transaction_id', $transaction_id )
                        ->set( 'type',    Lib\Entities\Payment::TYPE_2CHECKOUT )
                        ->set( 'status',  Lib\Entities\Payment::STATUS_COMPLETED )
                        ->set( 'total',   $cart_info['total_price'] )
                        ->set( 'token',   $this->getParameter( 'invoice_id' ) )
                        ->set( 'created', current_time( 'mysql' ) )
                        ->save();
                    $ca_list = $userData->save( $payment->get( 'id' ) );
                    Lib\NotificationSender::sendFromCart( $ca_list );
                    $payment->setDetails( $ca_list, $coupon )->save();
                }

                $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_2CHECKOUT, 'success' );

                @wp_redirect( $redirect );
                exit;
            }
        } else {
            header( 'Location: ' . wp_sanitize_redirect( add_query_arg( array(
                    'action'    => 'ab-2checkout-error',
                    'ab_fid'    => $this->getParameter( 'ab_fid' ),
                    'error_msg' => str_replace( ' ', '%20', __( 'Invalid session', 'bookly' ) )
                ), $redirect
            ) ) );
            exit;
        }
    }

    public function error()
    {
        $userData = new Lib\UserBookingData( $this->getParameter( 'ab_fid' ) );
        $userData->load();
        $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_2CHECKOUT, 'error', $this->getParameter( 'error_msg' ) );
        @wp_redirect( remove_query_arg( Lib\Payment\TwoCheckout::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
        exit;
    }

}