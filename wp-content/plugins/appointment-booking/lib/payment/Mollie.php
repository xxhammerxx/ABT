<?php
namespace Bookly\Lib\Payment;

use Bookly\Lib;

/**
 * Class Mollie
 */
class Mollie
{
    // Array for cleaning Mollie request
    public static $remove_parameters = array( 'action', 'ab_fid', 'error_msg' );

    public static function renderForm( $form_id )
    {
        $userData = new Lib\UserBookingData( $form_id );
        if ( $userData->load() ) {
            $html = '<form method="post" class="ab-mollie-form">';
            $html .= '<input type="hidden" name="action" value="ab-mollie-checkout"/>';
            $html .= '<input type="hidden" name="ab_fid" value="' . $form_id . '"/>';
            $html .= '<input type="hidden" name="response_url"/>';
            $html .= '<button class="ab-left ab-back-step ab-btn ladda-button" data-style="zoom-in" style="margin-right: 10px;" data-spinner-size="40"><span class="ladda-label">' . Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_button_back' ) . '</span></button>';
            $html .= '<button class="ab-right ab-next-step ab-btn ladda-button" data-style="zoom-in" data-spinner-size="40"><span class="ladda-label">' . Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_button_next' ) . '</span></button>';
            $html .= '</form>';

            echo $html;
        }
    }

    /**
     * Handles IPN messages
     */
    public static function ipn()
    {
        $api     = self::_getApi();
        $payment = $api->payments->get( $_REQUEST['id'] );
        Mollie::handlePayment( $payment );
    }

    /**
     * Check gateway data and if ok save payment info
     *
     * @param \Mollie_API_Object_Payment $details
     */
    public static function handlePayment( \Mollie_API_Object_Payment $details )
    {
        $pending_appointments = explode( ',', $details->metadata->pending );
        if ( $details->isPaid() ) {
            // Handle completed card & bank transfers here
            $payment  = Lib\Entities\Payment::query( 'p' )->leftJoin( 'CustomerAppointment', 'ca', 'ca.payment_id = p.id' )
                ->where( 'ca.id', current( $pending_appointments ) )->where( 'p.type', Lib\Entities\Payment::TYPE_MOLLIE )->findOne();
            $total    = (float) $payment->get( 'total' );
            $received = (float) $details->amount;

            if ( $payment->get( 'status' ) == Lib\Entities\Payment::STATUS_COMPLETED
                 || $received != $total
            ) {
                wp_send_json_success();
            } else {
                $payment->set( 'transaction_id', $details->profileId )
                    ->set( 'status', Lib\Entities\Payment::STATUS_COMPLETED )
                    ->set( 'token',  $details->id )
                    ->save();
                $ca_list = Lib\Entities\CustomerAppointment::query()->where( 'payment_id', $payment->get( 'id' ) )->find();
                Lib\NotificationSender::sendFromCart( $ca_list );
            }
        } elseif ( ! $details->isOpen() ) {
            /** @var Lib\Entities\CustomerAppointment $ca */
            foreach ( Lib\Entities\CustomerAppointment::query()->whereIn( 'id', $pending_appointments )->find() as $ca ) {
                $ca->deleteCascade();
            }
        }
        wp_send_json_success();
    }

    /**
     * Redirect to Mollie Payment page, or step payment.
     *
     * @param $form_id
     * @param Lib\UserBookingData $userData
     * @param $response_url
     */
    public static function paymentPage( $form_id, Lib\UserBookingData $userData, $response_url )
    {
        if ( get_option( 'ab_currency' ) != 'EUR' ) {
            $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_MOLLIE, 'error', __( 'Mollie accepts payments in Euro only.', 'bookly' ) );
            @wp_redirect( remove_query_arg( Lib\Payment\Payson::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
            exit;
        }
        $cart_info = $userData->cart->getInfo();
        $coupon  = $userData->getCoupon();
        $payment = new Lib\Entities\Payment();
        $payment->set( 'type', Lib\Entities\Payment::TYPE_MOLLIE )
            ->set( 'status',   Lib\Entities\Payment::STATUS_PENDING )
            ->set( 'created',  current_time( 'mysql' ) )
            ->set( 'total',    $cart_info['total_deposit_price'] )
            ->save();
        $ca_list = $userData->save( $payment->get( 'id' ) );
        try {
            $api = self::_getApi();
            $mollie_payment = $api->payments->create( array(
                'amount'       => $cart_info['total_price'],
                'description'  => $userData->cart->getItemsTitle( 125 ),
                'redirectUrl'  => $response_url . 'action=ab-mollie-response&ab_fid=' . $form_id,
                'webhookUrl'   => $response_url . 'action=ab-mollie-ipn',
                'metadata'     => array(
                    'pending'  => implode( ',', array_keys( $ca_list ) )
                ),
                'issuer'       => null
            ) );
            if ( $mollie_payment->isOpen() ) {
                if ( $coupon ) {
                    $coupon->claim();
                    $coupon->save();
                }
                $payment->setDetails( $ca_list, $coupon )->save();
                $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_MOLLIE, 'pending', $mollie_payment->id );
                header( 'Location: ' . $mollie_payment->getPaymentUrl() );
                exit;
            } else {
                self::_deleteAppointments( $ca_list );
                self::_redirectTo( $userData, 'error', __( 'Mollie error.', 'bookly' ) );
            }
        } catch ( \Exception $e ) {
            self::_deleteAppointments( $ca_list );
            $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_MOLLIE, 'error', $e->getMessage() );
            @wp_redirect( remove_query_arg( Lib\Payment\Payson::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
            exit;
        }
    }

    /**
     * @param Lib\Entities\CustomerAppointment[] $customer_appointments
     */
    private static function _deleteAppointments( $customer_appointments )
    {
        foreach ( $customer_appointments as $customer_appointment ) {
            $customer_appointment->deleteCascade();
        }
    }

    private static function _getApi()
    {
        include_once Lib\Plugin::getDirectory() . '/lib/payment/Mollie/API/Autoloader.php';
        $mollie = new \Mollie_API_Client();
        $mollie->setApiKey( get_option( 'ab_mollie_api_key' ) );

        return $mollie;
    }

    /**
     * Notification for customer
     *
     * @param Lib\UserBookingData $userData
     * @param string $status    success || error || processing
     * @param string $message
     */
    private static function _redirectTo( Lib\UserBookingData $userData, $status = 'success', $message = '' )
    {
        $userData->load();
        $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_MOLLIE, $status, $message );
        @wp_redirect( remove_query_arg( Lib\Payment\Mollie::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
        exit;
    }

    public static function getCancelledAppointments( $tr_id )
    {
        $api = self::_getApi();
        $mollie_payment = $api->payments->get( $tr_id );
        if ( $mollie_payment->isOpen() || $mollie_payment->isPaid() ) {
            return array();
        } else {
            return explode( ',', $mollie_payment->metadata->pending );
        }
    }

}