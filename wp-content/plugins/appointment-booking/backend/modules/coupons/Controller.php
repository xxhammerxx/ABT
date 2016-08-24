<?php
namespace Bookly\Backend\Modules\Coupons;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Backend\Modules\Coupons
 */
class Controller extends Lib\Base\Controller
{
    /**
     * Default action
     */
    public function index()
    {
        $this->enqueueStyles( array(
            'backend'  => array( 'bootstrap/css/bootstrap-theme.min.css', ),
            'frontend' => array( 'css/ladda.min.css', ),
        ) );

        $this->enqueueScripts( array(
            'backend' => array(
                'bootstrap/js/bootstrap.min.js' => array( 'jquery' ),
                'js/datatables.min.js' => array( 'jquery' ),
            ),
            'frontend' => array(
                'js/spin.min.js'  => array( 'jquery' ),
                'js/ladda.min.js' => array( 'jquery' ),
            ),
            'module' => array( 'js/coupons.js' => array( 'jquery' ) )
        ) );

        wp_localize_script( 'ab-coupons.js', 'BooklyL10n', array(
            'edit'         => __( 'Edit', 'bookly' ),
            'zeroRecords'  => __( 'No coupons found.', 'bookly' ),
            'processing'   => __( 'Processing...', 'bookly' ),
            'are_you_sure' => __( 'Are you sure?', 'bookly' ),
        ) );

        $this->render( 'index' );
    }

    /**
     * Get coupons list
     */
    public function executeGetCoupons()
    {
        wp_send_json_success( Lib\Entities\Coupon::query()->fetchArray() );
    }

    /**
     * Create/update coupon
     */
    public function executeSaveCoupon()
    {
        $form = new Forms\Coupon();
        $form->bind( $this->getParameter( 'data' ) );

        $data = $form->getData();
        if ( $data['discount'] < 0 || $data['discount'] > 100 ) {
            wp_send_json_error( array ( 'message' => __( 'Discount should be between 0 and 100.', 'bookly' ) ) );
        } elseif ( $data['deduction'] < 0 ) {
            wp_send_json_error( array ( 'message' => __( 'Deduction should be a positive number.', 'bookly' ) ) );
        } else {
            wp_send_json_success( $form->save()->getFields() );
        }
    }

    /**
     * Delete coupons.
     */
    public function executeDeleteCoupons()
    {
        $coupon_ids = array_map( 'intval', $this->getParameter( 'data', array() ) );
        Lib\Entities\Coupon::query()->delete()->whereIn( 'id', $coupon_ids )->execute();
        wp_send_json_success();
    }

    // Protected methods.

    /**
     * Override parent method to add 'wp_ajax_ab_' prefix
     * so current 'execute*' methods look nicer.
     *
     * @param string $prefix
     */
    protected function registerWpActions( $prefix = '' )
    {
        parent::registerWpActions( 'wp_ajax_ab_' );
    }

}