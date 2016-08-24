<?php
namespace Bookly\Frontend\Modules\Mollie;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Frontend\Modules\Mollie
 */
class Controller extends Lib\Base\Controller
{

    protected function getPermissions()
    {
        return array( '_this' => 'anonymous' );
    }

    public function checkout()
    {
        $form_id  = $this->getParameter( 'ab_fid' );
        $userData = new Lib\UserBookingData( $form_id );
        if ( $userData->load() ) {
            Lib\Payment\Mollie::paymentPage( $form_id, $userData, $this->getParameter( 'response_url' ) );
        }
    }

    /**
     * Redirect from Payment Form to Bookly page
     */
    public function response()
    {
        $form_id  = $this->getParameter( 'ab_fid' );
        $userData = new Lib\UserBookingData( $form_id );
        $userData->load();
        if ( $payment = Lib\Session::getFormVar( $form_id, 'payment' ) ) {
            if ( $payment['status'] == 'pending' ) {
                $cancel_appointments = Lib\Payment\Mollie::getCancelledAppointments( $payment['data'] );
                if ( empty( $cancel_appointments ) ) {
                    $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_MOLLIE, 'processing' );
                    @wp_redirect( remove_query_arg( Lib\Payment\Mollie::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
                } else {
                    /** @var Lib\Entities\CustomerAppointment $ca */
                    foreach ( Lib\Entities\CustomerAppointment::query()->whereIn( 'id', $cancel_appointments )->find() as $ca ) {
                        $ca->deleteCascade();
                    }
                    $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_MOLLIE, 'cancelled' );
                    @wp_redirect( remove_query_arg( Lib\Payment\Mollie::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
                }
            }
        }
        exit;
    }

}