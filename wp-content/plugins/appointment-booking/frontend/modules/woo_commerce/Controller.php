<?php
namespace Bookly\Frontend\Modules\WooCommerce;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Frontend\Modules\WooCommerce
 */
class Controller extends Lib\Base\Controller
{
    const VERSION = '1.0';

    private $product_id = 0;
    private $checkout_info = array();

    protected function getPermissions()
    {
        return array( '_this' => 'anonymous', );
    }

    public function __construct()
    {
        if ( get_option( 'ab_woocommerce_enabled' ) ) {
            $this->product_id = get_option( 'ab_woocommerce_product', 0 );

            add_action( 'woocommerce_get_item_data',            array( $this, 'getItemData' ), 10, 2 );
            add_action( 'woocommerce_payment_complete',         array( $this, 'paymentComplete' ) );
            add_action( 'woocommerce_order_status_completed',   array( $this, 'paymentComplete' ) );
            add_action( 'woocommerce_thankyou',                 array( $this, 'paymentComplete' ) );
            add_action( 'woocommerce_add_order_item_meta',      array( $this, 'addOrderItemMeta' ), 10, 3 );
            add_action( 'woocommerce_before_calculate_totals',  array( $this, 'beforeCalculateTotals' ) );
            add_action( 'woocommerce_after_order_itemmeta',     array( $this, 'orderItemMeta' ) );
            add_action( 'woocommerce_order_item_meta_end',      array( $this, 'orderItemMeta' ) );
            add_action( 'woocommerce_before_cart_contents',     array( $this, 'checkAvailableTimeForCart' ) );

            add_filter( 'woocommerce_quantity_input_args',      array( $this, 'quantityArgs' ), 10, 2 );
            add_filter( 'woocommerce_checkout_get_value',       array( $this, 'checkoutValue' ), 10, 2 );

            parent::__construct();
        }
    }

    /**
     * Verifies the availability of all appointments that are in the cart
     */
    public function checkAvailableTimeForCart()
    {
        $recalculate_totals = false;
        foreach ( WC()->cart->get_cart() as $wc_key => $wc_item ) {
            if ( array_key_exists( 'bookly', $wc_item ) ) {
                if ( ! isset( $wc_item['bookly']['version'] ) ) {
                    if ( $this->_migration( $wc_key, $wc_item ) === false ) {
                        // Removed item from cart.
                        continue;
                    }
                }
                $userData = new Lib\UserBookingData( null );
                $userData->fillData( $wc_item['bookly'] );
                $userData->cart->setItemsData( $wc_item['bookly']['items'] );
                if ( $wc_item['quantity'] > 1 ) {
                    foreach ( $userData->cart->getItems() as $cart_item ) {
                        // Equal appointments increase quantity
                        $cart_item->set( 'number_of_persons', $cart_item->get( 'number_of_persons' ) * $wc_item['quantity'] );
                    }
                }
                // Check if appointment's time is still available
                $failed_cart_key = $userData->cart->getFailedKey();
                if ( $failed_cart_key !== null ) {
                    $cart_item = $userData->cart->get( $failed_cart_key );
                    $slot = $cart_item->get( 'slots' );
                    $notice = strtr( __( 'Sorry, the time slot %date_time% for %service% has been already occupied.', 'bookly' ),
                        array(
                            '%service%'   => '<strong>' . $cart_item->getService()->getTitle() . '</strong>',
                            '%date_time%' => Lib\Utils\DateTime::formatDateTime( date( 'Y-m-d H:i:s', $slot[0][2] ) )
                    ) );
                    wc_print_notice( $notice, 'notice' );
                    WC()->cart->set_quantity( $wc_key, 0, false );
                    $recalculate_totals = true;
                }
            }
        }
        if ( $recalculate_totals ) {
            WC()->cart->calculate_totals();
        }
    }

    /**
     * Assign checkout value from appointment.
     *
     * @param $null
     * @param $field_name
     * @return string|null
     */
    public function checkoutValue( $null, $field_name )
    {
        if ( empty( $this->checkout_info ) ) {
            foreach ( WC()->cart->get_cart() as $wc_key => $wc_item ) {
                if ( array_key_exists( 'bookly', $wc_item ) ) {
                    if ( ! isset( $wc_item['bookly']['version'] ) || $wc_item['bookly']['version'] < self::VERSION ) {
                        if ( $this->_migration( $wc_key, $wc_item ) === false ) {
                            // Removed item from cart.
                            continue;
                        }
                    }
                    $full_name = $wc_item['bookly']['name'];
                    $this->checkout_info = array(
                        'billing_first_name' => strtok( $full_name, ' ' ),
                        'billing_last_name'  => strtok( '' ),
                        'billing_email'      => $wc_item['bookly']['email'],
                        'billing_phone'      => $wc_item['bookly']['phone']
                    );
                    break;
                }
            }
        }
        if ( array_key_exists( $field_name, $this->checkout_info ) ) {
            return $this->checkout_info[ $field_name ];
        }

        return null;
    }

    /**
     * Do bookings after checkout.
     *
     * @param $order_id
     */
    public function paymentComplete( $order_id )
    {
        $order = new \WC_Order( $order_id );
        $ca_list = array();
        foreach ( $order->get_items() as $item_id => $order_item ) {
            $data = wc_get_order_item_meta( $item_id, 'bookly' );
            if ( $data && ! isset ( $data['processed'] ) ) {
                $userData = new Lib\UserBookingData( null );
                $userData->fillData( $data );
                $userData->cart->setItemsData( $data['items'] );
                if ( $order_item['qty'] > 1 ) {
                    foreach ( $userData->cart->getItems() as $cart_item ) {
                        $cart_item->set( 'number_of_persons', $cart_item->get( 'number_of_persons' ) * $order_item['qty'] );
                    }
                }
                $ca_list = $userData->save();
                // Mark item as processed.
                $data['processed'] = true;
                wc_update_order_item_meta( $item_id, 'bookly', $data );
            }
        }
        if ( ! empty( $ca_list ) ) {
            Lib\NotificationSender::sendFromCart( $ca_list );
        }
    }

    /**
     * Change attr for WC quantity input
     *
     * @param $args
     * @param $product
     * @return mixed
     */
    function quantityArgs( $args, $product )
    {
        if ( $product->id == $this->product_id ) {
            $args['max_value'] = $args['input_value'];
            $args['min_value'] = $args['input_value'];
        }

        return $args;
    }

    /**
     * Change item price in cart.
     *
     * @param $cart_object
     */
    public function beforeCalculateTotals( $cart_object )
    {
        foreach ( $cart_object->cart_contents as $wc_key => $wc_item ) {
            if ( isset ( $wc_item['bookly'] ) ) {
                if ( ! isset( $wc_item['bookly']['version'] ) || $wc_item['bookly']['version'] < self::VERSION ) {
                    if ( $this->_migration( $wc_key, $wc_item ) === false ) {
                        // Removed item from cart.
                        continue;
                    }
                }
                $userData = new Lib\UserBookingData( null );
                $userData->fillData( $wc_item['bookly'] );
                $userData->cart->setItemsData( $wc_item['bookly']['items'] );
                $cart_info = $userData->cart->getInfo();
                $wc_item['data']->price = $cart_info['total_price'];
            }
        }
    }

    public function addOrderItemMeta( $item_id, $values, $wc_key )
    {
        if ( isset ( $values['bookly'] ) ) {
            wc_update_order_item_meta( $item_id, 'bookly', $values['bookly'] );
        }
    }

    /**
     * Get item data for cart.
     *
     * @param $other_data
     * @param $wc_item
     * @return array
     */
    function getItemData( $other_data, $wc_item )
    {
        if ( isset ( $wc_item['bookly'] ) ) {
            $userData = new Lib\UserBookingData( null );
            $info = array();
            if ( isset( $wc_item['bookly'] ) ) {
                if ( isset( $wc_item['bookly']['version'] ) && $wc_item['bookly']['version'] == self::VERSION ) {
                    $userData->fillData( $wc_item['bookly'] );
                    $userData->cart->setItemsData( $wc_item['bookly']['items'] );
                    foreach ( $userData->cart->getItems() as $cart_item ) {
                        $slots = $cart_item->get( 'slots' );
                        $appointment_datetime = date( 'Y-m-d H:i:s', $slots[0][2] );
                        if ( get_option( 'ab_settings_use_client_time_zone' ) ) {
                            $appointment_datetime = Lib\Utils\DateTime::applyTimeZoneOffset(
                                $appointment_datetime, $userData->get( 'time_zone_offset' )
                            );
                        }
                        $service = $cart_item->getService();
                        $info[]  = strtr( get_option( 'ab_woocommerce_cart_info_value' ), array(
                            '[[APPOINTMENT_TIME]]'  => Lib\Utils\DateTime::formatTime( $appointment_datetime ),
                            '[[APPOINTMENT_DATE]]'  => Lib\Utils\DateTime::formatDate( $appointment_datetime ),
                            '[[CATEGORY_NAME]]'     => $service->getCategoryName(),
                            '[[SERVICE_NAME]]'      => $service->getTitle(),
                            '[[SERVICE_PRICE]]'     => $cart_item->getServicePrice(),
                            '[[STAFF_NAME]]'        => $cart_item->getStaff()->getName(),
                            '[[NUMBER_OF_PERSONS]]' => $cart_item->get( 'number_of_persons' ),
                        ) );
                    }
                }
            }

            $other_data[] = array( 'name' => get_option( 'ab_woocommerce_cart_info_name' ), 'value' => implode( PHP_EOL . PHP_EOL, $info ) );
        }

        return $other_data;
    }

    /**
     * Print appointment details inside order items in the backend.
     *
     * @param $item_id
     */
    public function orderItemMeta( $item_id )
    {
        $data = wc_get_order_item_meta( $item_id, 'bookly' );
        if ( $data ) {
            $other_data = $this->getItemData( array(), array( 'bookly' => $data ) );
            echo '<br/>' . $other_data[0]['name'] . '<br/>' . nl2br( $other_data[0]['value'] );
        }
    }

    /**
     * Add product to cart
     *
     * @return string JSON
     */
    public function executeAddToWoocommerceCart()
    {
        if ( ! get_option( 'ab_woocommerce_enabled' ) ) {
            exit;
        }
        $response = null;
        $userData = new Lib\UserBookingData( $this->getParameter( 'form_id' ) );

        if ( $userData->load() ) {
            $session = WC()->session;
            /** @var \WC_Session_Handler $session */
            if ( $session instanceof \WC_Session_Handler && $session->get_session_cookie() === false ) {
                $session->set_customer_session_cookie( true );
            }
            if ( $userData->cart->getFailedKey() === null ) {
                $bookly = array(
                    'version' => self::VERSION,
                    'name'    => $userData->get( 'name' ),
                    'email'   => $userData->get( 'email' ),
                    'phone'   => $userData->get( 'phone' ),
                    'items'   => $userData->cart->getItemsData(),
                    'time_zone_offset' => $userData->get( 'time_zone_offset' ),
                );
                // Qnt 1 product in $userData exist value with number_of_persons
                WC()->cart->add_to_cart( $this->product_id, 1, '', array(), array( 'bookly' => $bookly ) );
                $response = array( 'success' => true );
            } else {
                $response = array( 'success' => false, 'error' => __( 'The selected time is not available anymore. Please, choose another time slot.', 'bookly' ) );
            }
        } else {
            $response = array( 'success' => false, 'error' => __( 'Session error.', 'bookly' ) );
        }
        wp_send_json( $response );
    }

    /**
     * Migration deprecated cart items.
     *
     * @param $wc_key
     * @param $data
     * @return bool
     */
    private function _migration( $wc_key, $data )
    {
        // The current implementation only remove cart items with deprecated format.
        WC()->cart->set_quantity( $wc_key, 0, false );
        WC()->cart->calculate_totals();

        return false;
    }

    /**
     * Override parent method to add 'wp_ajax_ab_' prefix
     * so current 'execute*' methods look nicer.
     *
     * @param string $prefix
     */
    protected function registerWpActions( $prefix = '' )
    {
        parent::registerWpActions( 'wp_ajax_ab_' );
        parent::registerWpActions( 'wp_ajax_nopriv_ab_' );
    }

}