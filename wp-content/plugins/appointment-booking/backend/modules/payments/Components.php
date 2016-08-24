<?php
namespace Bookly\Backend\Modules\Payments;

use Bookly\Lib;

/**
 * Class Components
 * @package Bookly\Backend\Modules\Payments
 */
class Components extends Lib\Base\Components
{
    /**
     * Render payment details modal window.
     * @throws \Exception
     */
    public function renderPaymentDetailsModal()
    {
        $this->enqueueScripts( array(
            'module' => array( 'js/payment_details.js' => array( 'jquery' ), ),
        ) );

        $this->render( '_details_modal' );
    }

}