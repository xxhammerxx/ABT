<?php
namespace Bookly\Frontend\Modules\Booking;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Frontend\Modules\Booking
 */
class Controller extends Lib\Base\Controller
{
    private $info_text_codes = array();

    protected function getPermissions()
    {
        return array( '_this' => 'anonymous' );
    }

    /**
     * Render Bookly shortcode.
     *
     * @param $attributes
     * @return string
     */
    public function renderShortCode( $attributes )
    {
        global $sitepress;

        $assets = '';

        if ( get_option( 'ab_settings_link_assets_method' ) == 'print' ) {
            $this->print_assets = ! wp_script_is( 'bookly', 'done' );
            if ( $this->print_assets ) {
                ob_start();

                // The styles and scripts are registered in Frontend.php
                wp_print_styles( 'ab-picker-date' );
                wp_print_styles( 'ab-picker-classic-date' );
                wp_print_styles( 'ab-picker' );
                wp_print_styles( 'ab-ladda-themeless' );
                wp_print_styles( 'ab-ladda-min' );
                wp_print_styles( 'ab-main' );
                wp_print_styles( 'ab-columnizer' );
                wp_print_styles( 'ab-intlTelInput' );

                wp_print_scripts( 'ab-spin' );
                wp_print_scripts( 'ab-ladda' );
                wp_print_scripts( 'ab-picker' );
                wp_print_scripts( 'ab-picker-date' );
                wp_print_scripts( 'ab-hammer' );
                wp_print_scripts( 'ab-jq-hammer' );
                wp_print_scripts( 'ab-intlTelInput' );
                // Android animation.
                if ( stripos( strtolower( $_SERVER['HTTP_USER_AGENT'] ), 'android' ) !== false ) {
                    wp_print_scripts( 'ab-jquery-animate-enhanced' );
                }
                wp_print_scripts( 'bookly' );

                $assets = ob_get_clean();
            }
        } else {
            $this->print_assets = true; // to print CSS in template.
        }

        // Generate unique form id.
        $this->form_id = uniqid();

        // Find bookings with any of payment statuses ( PayPal, 2Checkout, PayU Latam ).
        $this->status = array( 'booking' => 'new' );
        foreach ( Lib\Session::getAllFormsData() as $form_id => $data ) {
            if ( isset ( $data['payment'] ) ) {
                if ( ! isset ( $data['payment']['processed'] ) ) {
                    switch ( $data['payment']['status'] ) {
                        case 'success':
                        case 'processing':
                            $this->form_id = $form_id;
                            $this->status = array( 'booking' => 'finished' );
                            break;
                        case 'cancelled':
                        case 'error':
                            $this->form_id = $form_id;
                            end( $data['cart'] );
                            $this->status = array( 'booking' => 'cancelled', 'cart_key' => key( $data['cart'] ) );
                            break;
                    }
                    // Mark this form as processed for cases when there are more than 1 booking form on the page.
                    $data['payment']['processed'] = true;
                    Lib\Session::setFormVar( $form_id, 'payment', $data['payment'] );
                }
            } elseif ( $data['last_touched'] + 30 * MINUTE_IN_SECONDS < time() ) {
                // Destroy forms older than 30 min.
                Lib\Session::destroyFormData( $form_id );
            }
        }

        // Handle shortcode attributes.
        $hide_date_and_time = (bool) @$attributes['hide_date_and_time'];
        $fields_to_hide = isset ( $attributes['hide'] ) ? explode( ',', $attributes['hide'] ) : array();
        $staff_member_id = intval( @$_GET['staff_id'] ?: @$attributes['staff_member_id'] );

        $attrs = array(
            'location_id'            => intval( @$_GET['loc_id']     ?: @$attributes['location_id'] ),
            'category_id'            => intval( @$_GET['cat_id']     ?: @$attributes['category_id'] ),
            'service_id'             => intval( @$_GET['service_id'] ?: @$attributes['service_id'] ),
            'staff_member_id'        => $staff_member_id,
            'hide_categories'        => in_array( 'categories',      $fields_to_hide ) ? true : (bool) @$attributes['hide_categories'],
            'hide_services'          => in_array( 'services',        $fields_to_hide ) ? true : (bool) @$attributes['hide_services'],
            'hide_staff_members'     => ( in_array( 'staff_members', $fields_to_hide ) ? true : (bool) @$attributes['hide_staff_members'] )
                                     && ( get_option( 'ab_appearance_required_employee' ) ? $staff_member_id : true ),
            'hide_date'              => $hide_date_and_time ? true : in_array( 'date',       $fields_to_hide ),
            'hide_week_days'         => $hide_date_and_time ? true : in_array( 'week_days',  $fields_to_hide ),
            'hide_time_range'        => $hide_date_and_time ? true : in_array( 'time_range', $fields_to_hide ),
            'show_number_of_persons' => (bool) @$attributes['show_number_of_persons'],
            // Add-ons.
            'hide_locations'         => true,
            'hide_quantity'          => true,
        );
        // Set service step attributes for Add-ons.
        if ( Lib\Config::locationsEnabled() ) {
            $attrs['hide_locations'] = in_array( 'locations', $fields_to_hide );
        }
        if ( Lib\Config::consecutiveAppointmentsEnabled() ){
            $attrs['hide_quantity']  = in_array( 'quantity',  $fields_to_hide );
        }

        $service_part1 = (
            ! $attrs['show_number_of_persons'] &&
            $attrs['hide_categories'] &&
            $attrs['hide_services'] &&
            $attrs['service_id'] &&
            $attrs['hide_staff_members'] &&
            $attrs['hide_quantity']
        );
        $service_part2 = (
            $attrs['hide_date'] &&
            $attrs['hide_week_days'] &&
            $attrs['hide_time_range']
        );
        if ( $service_part1 && $service_part2 ) {
            // Store attributes in session for later use in Time step.
            Lib\Session::setFormVar( $this->form_id, 'attrs', $attrs );
            Lib\Session::setFormVar( $this->form_id, 'last_touched', time() );
        }
        $this->skip_steps = array(
            'service_part1' => (int) $service_part1,
            'service_part2' => (int) $service_part2,
            'extras' => (int) ( ! Lib\Config::extrasEnabled() )
        );
        // Prepare URL for AJAX requests.
        $this->ajax_url = admin_url( 'admin-ajax.php' );
        // Support WPML.
        if ( $sitepress instanceof \SitePress ) {
            $this->ajax_url .= ( strpos( $this->ajax_url, '?' ) ? '&' : '?' ) . 'lang=' . $sitepress->get_current_language();
        }

        return $assets . $this->render( 'short_code', compact( 'attrs' ), false );
    }

    /**
     * 1. Step service.
     *
     * @return string JSON
     */
    public function executeRenderService()
    {
        $response = null;
        $form_id  = $this->getParameter( 'form_id' );

        if ( $form_id ) {
            $userData = new Lib\UserBookingData( $form_id );
            $userData->load();

            if ( $this->hasParameter( 'new_chain' ) ) {
                $userData->resetChain();
            }

            if ( $this->hasParameter( 'edit_cart_item' ) ) {
                $cart_key = $this->getParameter( 'edit_cart_item' );
                $userData->set( 'edit_cart_keys', array( $cart_key ) );
                $userData->setChainFromCartItem( $cart_key );
            }

            if ( get_option( 'ab_settings_use_client_time_zone' ) ) {
                $time_zone_offset = $this->getParameter( 'time_zone_offset' );
                $userData->set( 'time_zone_offset', $time_zone_offset );
                $userData->set(
                    'date_from',
                    date(
                        'Y-m-d',
                        current_time( 'timestamp' ) +
                        Lib\Config::getMinimumTimePriorBooking() -
                        ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS + $time_zone_offset * 60 )
                    )
                );
            }

            $this->_prepareProgressTracker( 1, $userData );
            $this->info_text = $this->_prepareInfoText( 1, Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_info_service_step' ), $userData );

            // Available days and times.
            $days_times = Lib\Config::getDaysAndTimes( $userData->get( 'time_zone_offset' ) );
            // Prepare week days that need to be checked.
            $days_checked = $userData->get( 'days' );
            if ( empty( $days_checked ) ) {
                // Check all available days.
                $days_checked = array_keys( $days_times['days'] );
            }
            $bounding = Lib\Config::getBoundingDaysForPickadate( $userData->get( 'time_zone_offset' ) );

            $casest = Lib\Config::getCaSeSt();

            if ( class_exists( '\BooklyLocations\Lib\Plugin' ) ) {
                $locasest = $casest['locations'];
            } else {
                $locasest = array();
            }

            $response = array(
                'success'    => true,
                'html'       => $this->render( '1_service', array(
                    'userData'      => $userData,
                    'days'          => $days_times['days'],
                    'times'         => $days_times['times'],
                    'days_checked'  => $days_checked,
                    'show_cart_btn' => $this->_showCartButton( $userData )
                ), false ),
                'locations'  => $locasest,
                'categories' => $casest['categories'],
                'staff'      => $casest['staff'],
                'services'   => $casest['services'],
                'date_max'   => $bounding['date_max'],
                'date_min'   => $bounding['date_min'],
                'chain'      => $userData->chain->getItemsData(),
            );
        } else {
            $response = array( 'success' => false, 'error_code' => 2, 'error' => __( 'Form ID error.', 'bookly' ) );
        }

        // Output JSON response.
        wp_send_json( $response );
    }

    /**
     * 2. Step Extras.
     *
     * @return string JSON
     */
    public function executeRenderExtras()
    {
        $response = null;
        $userData = new Lib\UserBookingData( $this->getParameter( 'form_id' ) );
        $loaded   = $userData->load();

        if ( ! $loaded && Lib\Session::hasFormVar( $this->getParameter( 'form_id' ), 'attrs' ) ) {
            $loaded = true;
        }

        if ( $loaded ) {
            if ( $this->hasParameter( 'new_chain' ) ) {
                $this->_setDataForSkippedServiceStep( $userData );
            }

            if ( $this->hasParameter( 'edit_cart_item' ) ) {
                $cart_key = $this->getParameter( 'edit_cart_item' );
                $userData->set( 'edit_cart_keys', array( $cart_key ) );
                $userData->setChainFromCartItem( $cart_key );
            }

            $this->_prepareProgressTracker( 2, $userData );
            $info_text = $this->_prepareInfoText( 2, Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_info_extras_step' ), $userData );
            $show_cart_btn = $this->_showCartButton( $userData );

            // Prepare money format for JavaScript.
            $price     = Lib\Utils\Common::formatPrice( 1 );
            $format    = str_replace( array( '0', '.', ',' ), '', $price );
            $precision = substr_count( $price, '0' );

            $response = array(
                'success'  => true,
                'currency' => array( 'format' => $format, 'precision' => $precision ),
                'html'     => apply_filters( 'bookly_extras_render_booking_step', '', $userData, $show_cart_btn, $info_text, $this->progress_tracker ),
            );
        } else {
            $response = array( 'success' => false, 'error_code' => 1, 'error' => __( 'Session error.', 'bookly' ) );
        }

        // Output JSON response.
        wp_send_json( $response );
    }

    /**
     * 3. Step time.
     *
     * @return string JSON
     */
    public function executeRenderTime()
    {
        $response = null;
        $userData = new Lib\UserBookingData( $this->getParameter( 'form_id' ) );
        $loaded   = $userData->load();

        if ( ! $loaded && Lib\Session::hasFormVar( $this->getParameter( 'form_id' ), 'attrs' ) ) {
            $loaded = true;
        }

        if ( $loaded ) {
            if ( $this->hasParameter( 'new_chain' ) ) {
                $this->_setDataForSkippedServiceStep( $userData );
            }

            if ( $this->hasParameter( 'edit_cart_item' ) ) {
                $cart_key = $this->getParameter( 'edit_cart_item' );
                $userData->set( 'edit_cart_keys', array( $cart_key ) );
                $userData->setChainFromCartItem( $cart_key );
            }

            $availableTime = new Lib\AvailableTime( $userData );
            if ( $this->hasParameter( 'selected_date' ) ) {
                $availableTime->setSelectedDate( $this->getParameter( 'selected_date' ) );
            } else {
                $availableTime->setSelectedDate( $userData->get( 'date_from' ) );
            }
            $availableTime->load();

            $this->_prepareProgressTracker( 3, $userData );
            $this->info_text = $this->_prepareInfoText( 3, Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_info_time_step' ), $userData );

            // Render slots by groups (day or month).
            $slots = $userData->get( 'slots' );
            $selected_timestamp = isset ( $slots[0][2] ) ? $slots[0][2] : null;
            $slots = array();
            foreach ( $availableTime->getSlots() as $group => $group_slots ) {
                $slots[ $group ] = preg_replace( '/>\s+</', '><', $this->render( '_time_slots', array(
                    'group' => $group,
                    'slots' => $group_slots,
                    'is_all_day_service' => $availableTime->isAllDayService(),
                    'selected_timestamp' => $selected_timestamp,
                ), false ) );
            }

            // Set response.
            $response = array(
                'success'        => true,
                'has_slots'      => ! empty ( $slots ),
                'has_more_slots' => $availableTime->hasMoreSlots(),
                'day_one_column' => Lib\Config::showDayPerColumn(),
                'slots'          => $slots,
                'html'           => $this->render( '3_time', array(
                    'date'          => Lib\Config::showCalendar() ? $availableTime->getSelectedDateForPickadate() : null,
                    'has_slots'     => ! empty ( $slots ),
                    'show_cart_btn' => $this->_showCartButton( $userData )
                ), false ),
            );

            if ( Lib\Config::showCalendar() ) {
                $bounding = Lib\Config::getBoundingDaysForPickadate( $userData->get( 'time_zone_offset' ) );
                $response['date_max'] = $bounding['date_max'];
                $response['date_min'] = $bounding['date_min'];
                $response['disabled_days'] = $availableTime->getDisabledDaysForPickadate();
            }
        } else {
            $response = array( 'success' => false, 'error_code' => 1, 'error' => __( 'Session error.', 'bookly' ) );
        }

        // Output JSON response.
        wp_send_json( $response );
    }

    /**
     * Render next time for step Time.
     *
     * @throws \Exception
     * @return string JSON
     */
    public function executeRenderNextTime()
    {
        $userData = new Lib\UserBookingData( $this->getParameter( 'form_id' ) );

        if ( $userData->load() ) {
            $availableTime = new Lib\AvailableTime( $userData );
            $availableTime->setLastFetchedSlot( $this->getParameter( 'last_slot' ) );
            $availableTime->load();

            $slots = $userData->get( 'slots' );
            $selected_timestamp = isset ( $slots[0][2] ) ? $slots[0][2] : null;
            $html = '';
            foreach ( $availableTime->getSlots() as $group => $group_slots ) {
                $html .= $this->render( '_time_slots', array(
                    'group' => $group,
                    'slots' => $group_slots,
                    'is_all_day_service' => $availableTime->isAllDayService(),
                    'selected_timestamp' => $selected_timestamp,
                ), false );
            }

            // Set response.
            $response = array(
                'success'        => true,
                'html'           => preg_replace( '/>\s+</', '><', $html ),
                'has_slots'      => $html != '',
                'has_more_slots' => $availableTime->hasMoreSlots(), // show/hide the next button
            );
        } else {
            $response = array( 'success' => false, 'error_code' => 1, 'error' => __( 'Session error.', 'bookly' ) );
        }

        // Output JSON response.
        wp_send_json( $response );
    }

    /**
     * 4. Step cart.
     *
     * @return string JSON
     */
    public function executeRenderCart()
    {
        $userData = new Lib\UserBookingData( $this->getParameter( 'form_id' ) );
        $depositEnabled = Lib\Config::depositEnabled();

        if ( $userData->load() ) {
            if ( $this->hasParameter( 'add_to_cart' ) ) {
                $userData->addChainToCart();
            }
            $this->_prepareProgressTracker( 4, $userData );
            $this->info_text = $this->_prepareInfoText( 4, Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_info_cart_step' ), $userData );
            $cart_items = array();
            $use_client_time_zone = get_option( 'ab_settings_use_client_time_zone' );
            $cart_columns = get_option( 'ab_cart_show_columns', array() );
            foreach ( $userData->cart->getItems() as $cart_key => $cart_item ) {
                $slots = $cart_item->get( 'slots' );
                $timestamp = $slots[0][2];
                if ( $use_client_time_zone ) {
                    $timestamp -= get_option( 'gmt_offset' ) * HOUR_IN_SECONDS + $userData->get( 'time_zone_offset' ) * 60;
                }
                foreach ( $cart_columns as $column => $attr ) {
                    if ( $attr['show'] ) {
                        switch ( $column ) {
                            case 'service':
                                $cart_items[ $cart_key ][] = $cart_item->getService()->getTitle();
                                break;
                            case 'date':
                                $cart_items[ $cart_key ][] = Lib\Utils\DateTime::formatDate( $timestamp );
                                break;
                            case 'time':
                                $cart_items[ $cart_key ][] = Lib\Utils\DateTime::formatTime( $timestamp );
                                break;
                            case 'employee':
                                $cart_items[ $cart_key ][] = $cart_item->getStaff()->getName();
                                break;
                            case 'price':
                                $cart_items[ $cart_key ][] = ( $cart_item->get( 'number_of_persons' ) > 1 ? $cart_item->get( 'number_of_persons' ) . ' &times; ' : '' ) . Lib\Utils\Common::formatPrice( $cart_item->getServicePrice() );
                                break;
                            case 'deposit':
                                $deposit = $cart_item->getDepositPrice( true );
                                if ( $depositEnabled ) {
                                    $cart_items[ $cart_key ][] = $deposit;
                                }
                                break;
                        }
                    }
                }
            }

            $cart_info = $userData->cart->getInfo( false );   // without coupon
            $columns   = array();
            $position  = 0;
            $price_position = -1;
            foreach ( $cart_columns as $column => $attr ) {
                if ( $attr['show'] ) {
                    switch ( $column ) {
                        case 'service':
                            $columns[] = Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_label_service' );
                            break;
                        case 'date':
                            $columns[] = __( 'Date', 'bookly' );
                            break;
                        case 'time':
                            $columns[] = __( 'Time', 'bookly' );
                            break;
                        case 'employee':
                            $columns[] = Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_label_employee' );
                            break;
                        case 'price':
                            $columns[] = __( 'Price', 'bookly' );
                            $price_position = $position;
                            break;
                        case 'deposit':
                            if ( $depositEnabled ) {
                                $columns[] = __( 'Deposit', 'bookly' );
                            }
                            break;
                    }
                    $position ++;
                }
            }
            $response = array(
                'success' => true,
                'html'    => $this->render( '4_cart', array(
                    'cart_items'     => $cart_items,
                    'total'          => $cart_info['total_price'],
                    'deposit_total'  => $cart_info['total_deposit_price'],
                    'columns'        => $columns,
                    'price_position' => $price_position,
                    'depositEnabled' => $depositEnabled,
                ), false ),
                'depositEnabled' => $depositEnabled
            );
        } else {
            $response = array( 'success' => false, 'error_code' => 1, 'error' => __( 'Session error.', 'bookly' ) );
        }

        // Output JSON response.
        wp_send_json( $response );
    }

    /**
     * 5. Step details.
     *
     * @return string JSON
     */
    public function executeRenderDetails()
    {
        $userData = new Lib\UserBookingData( $this->getParameter( 'form_id' ) );

        if ( $userData->load() ) {
            if ( ! Lib\Config::showStepCart() ) {
                $userData->addChainToCart();
            }
            $cf_data  = array();
            if ( Lib\Config::customFieldsPerService() ) {
                // Prepare custom fields data per service.
                foreach ( $userData->cart->getItems() as $cart_key => $cart_item ) {
                    $data = array();
                    foreach ( $cart_item->get( 'custom_fields' ) as $field ) {
                        $data[ $field['id'] ] = $field['value'];
                    }
                    if ( $cart_item->getService()->get( 'type' ) == Lib\Entities\Service::TYPE_COMPOUND ) {
                        $service_id = current( $cart_item->getService()->getSubServices() )->get( 'service_id' );
                    } else {
                        $service_id = $cart_item->get( 'service_id' );
                    }
                    $cf_data[ $cart_key ] = array(
                        'service_title' => Lib\Entities\Service::find( $cart_item->get( 'service_id' ) )->getTitle(),
                        'custom_fields' => Lib\Utils\Common::getTranslatedCustomFields( $service_id ),
                        'data'          => $data,
                    );
                }
            } else {
                $cart_items = $userData->cart->getItems();
                $cart_item  = array_pop( $cart_items );
                $data       = array();
                foreach ( $cart_item->get( 'custom_fields' ) as $field ) {
                    $data[ $field['id'] ] = $field['value'];
                }
                $cf_data[] = array(
                    'custom_fields' => Lib\Utils\Common::getTranslatedCustomFields( null ),
                    'data'          => $data,
                );
            }

            $this->_prepareProgressTracker( 5, $userData );
            $this->info_text = $this->_prepareInfoText( 5, Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_info_details_step' ), $userData );
            $this->info_text_guest = ( get_current_user_id() == 0 ) ? $this->_prepareInfoText( 3, Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_info_details_step_guest' ), $userData ) : '';
            if ( strpos( get_option( 'ab_custom_fields' ), '"captcha"' ) !== false ) {
                // Init Captcha.
                Lib\Captcha\Captcha::init( $this->getParameter( 'form_id' ) );
            }

            $response = array(
                'success' => true,
                'html'    => $this->render( '5_details', array(
                    'userData'    => $userData,
                    'cf_data'     => $cf_data,
                    'captcha_url' => admin_url( 'admin-ajax.php?action=ab_captcha&form_id=' . $this->getParameter( 'form_id' ) . '&' . microtime( true ) ),
                    'show_service_title' => Lib\Config::customFieldsPerService() && count( $cf_data ) > 1,
                ), false )
            );
        } else {
            $response = array( 'success' => false, 'error_code' => 1, 'error' => __( 'Session error.', 'bookly' ) );
        }

        // Output JSON response.
        wp_send_json( $response );
    }

    /**
     * 6. Step payment.
     *
     * @return string JSON
     */
    public function executeRenderPayment()
    {
        $userData = new Lib\UserBookingData( $this->getParameter( 'form_id' ) );

        if ( $userData->load() ) {
            $payment_disabled = Lib\Config::isPaymentDisabled();
            if ( ! Lib\Config::showStepCart() ) {
                $userData->addChainToCart();
            }
            $cart_info = $userData->cart->getInfo();
            if ( $cart_info['total_price'] <= 0 ) {
                $payment_disabled = true;
            }

            if ( $payment_disabled == false ) {
                $this->form_id   = $this->getParameter( 'form_id' );
                $this->_prepareProgressTracker( 6, $userData );
                $this->info_text = $this->_prepareInfoText( 6, Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_info_payment_step' ), $userData );
                $this->info_text_coupon = $this->_prepareInfoText( 6, Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_info_coupon' ), $userData );

                // Set response.
                $response = array(
                    'success'  => true,
                    'disabled' => false,
                    'html'     => $this->render( '6_payment', array(
                        'coupon_code'        => $userData->get( 'coupon' ),
                        'payment'            => $userData->extractPaymentStatus(),
                        'pay_local'          => get_option( 'ab_settings_pay_locally' ) != 'disabled',
                        'pay_paypal'         => get_option( 'ab_paypal_type' ) != 'disabled',
                        'pay_stripe'         => get_option( 'ab_stripe' ) != 'disabled',
                        'pay_2checkout'      => get_option( 'ab_2checkout' ) != 'disabled',
                        'pay_authorizenet'   => get_option( 'ab_authorizenet_type' ) != 'disabled',
                        'pay_payulatam'      => get_option( 'ab_payulatam' ) != 'disabled',
                        'pay_payson'         => get_option( 'ab_payson' ) != 'disabled',
                        'pay_mollie'         => get_option( 'ab_mollie' ) != 'disabled',
                    ), false )
                );
            } else {
                $response = array(
                    'success'  => true,
                    'disabled' => true,
                );
            }
        } else {
            $response = array( 'success' => false, 'error_code' => 1, 'error' => __( 'Session error.', 'bookly' ) );
        }

        // Output JSON response.
        wp_send_json( $response );
    }

    /**
     * 7. Step done ( complete ).
     *
     * @return string JSON
     */
    public function executeRenderComplete()
    {
        $userData = new Lib\UserBookingData( $this->getParameter( 'form_id' ) );

        if ( $userData->load() ) {
            $this->_prepareProgressTracker( 7, $userData );
            $payment = $userData->extractPaymentStatus();
            do {
                if ( $payment ) {
                    switch ( $payment['status'] ) {
                        case 'processing':
                            $this->info_text = __( 'Your payment has been accepted for processing.', 'bookly' );
                            break ( 2 );
                    }
                }
                $this->info_text = $this->_prepareInfoText( 7, Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_info_complete_step' ), $userData );
            } while ( 0 );

            $response = array (
                'success' => true,
                'html'    => $this->render( '7_complete', array(), false ),
            );
        } else {
            $response = array( 'success' => false, 'error_code' => 1, 'error' => __( 'Session error.', 'bookly' ) );
        }

        // Output JSON response.
        wp_send_json( $response );
    }


    /**
     * Save booking data in session.
     */
    public function executeSessionSave()
    {
        $form_id = $this->getParameter( 'form_id' );
        $errors  = array();
        if ( $form_id ) {
            $userData = new Lib\UserBookingData( $form_id );
            $userData->load();
            $parameters = $this->getParameters();
            $errors = $userData->validate( $parameters );
            if ( empty ( $errors ) ) {
                if ( $this->hasParameter( 'extras' ) ) {
                    $parameters['chain'] = $userData->chain->getItemsData();
                    foreach ( $parameters['chain'] as $key => &$item ) {
                        // Decode extras.
                        $item['extras'] = json_decode( $parameters['extras'][ $key ], true );
                    }
                } elseif ( $this->hasParameter( 'slots' ) ) {
                    // Decode slots.
                    $parameters['slots'] = json_decode( $parameters['slots'], true );
                } elseif ( $this->hasParameter( 'captcha_ids' ) ) {
                    $parameters['captcha_ids'] = json_decode( $parameters['captcha_ids'], true );
                    foreach ( $parameters['cart'] as &$cart_item ) {
                        // Remove captcha from custom fields.
                        $cart_item['custom_fields'] = array_filter( json_decode( $cart_item['custom_fields'], true ), function ( $field ) use ( $parameters ) {
                            return ! in_array( $field['id'], $parameters['captcha_ids'] );
                        } );
                    }
                    if ( ! Lib\Config::customFieldsPerService() ) {
                        // Copy custom fields to all cart items.
                        $cart = array();
                        foreach ( $userData->cart->getItems() as $cart_key => $_cart_item ) {
                            $cart[ $cart_key ] = $parameters['cart'][0];
                        }
                        $parameters['cart'] = $cart;
                    }
                }
                $userData->fillData( $parameters );
            }
        }

        // Output JSON response.
        wp_send_json( $errors );
    }

    /**
     * Save cart appointments.
     */
    public function executeSaveAppointment()
    {
        $userData = new Lib\UserBookingData( $this->getParameter( 'form_id' ) );

        if ( $userData->load() ) {
            $failed_cart_key = $userData->cart->getFailedKey();
            if ( $failed_cart_key === null ) {
                $cart_info = $userData->cart->getInfo();
                $total     = $cart_info['total_price'];
                $is_payment_disabled    = Lib\Config::isPaymentDisabled();
                $is_pay_locally_enabled = Lib\Config::isPayLocallyEnabled();
                if ( $is_payment_disabled || $is_pay_locally_enabled || $total <= 0 ) {
                    // Handle coupon.
                    $coupon = $userData->getCoupon();
                    if ( $coupon ) {
                        $coupon->claim();
                        $coupon->save();
                    }
                    // Handle payment.
                    $payment_id = null;
                    if ( ! $is_payment_disabled ) {
                        $payment = new Lib\Entities\Payment();
                        $payment->set( 'status', Lib\Entities\Payment::STATUS_COMPLETED )
                            ->set( 'created',    current_time( 'mysql' ) );
                        if ( $coupon && $total <= 0 ) {
                            // Create fake payment record for 100% discount coupons.
                            $payment->set( 'type', Lib\Entities\Payment::TYPE_COUPON )->set( 'total', '0.00' )->save();
                            $payment_id = $payment->get( 'id' );
                        } elseif ( $is_pay_locally_enabled && $total > 0 ) {
                            // Create record for local payment.
                            $payment->set( 'type', Lib\Entities\Payment::TYPE_LOCAL )->set( 'total', $total )->save();
                            $payment_id = $payment->get( 'id' );
                        }
                    }
                    // Save cart.
                    $ca_list = $userData->save( $payment_id );
                    // Send notifications.
                    Lib\NotificationSender::sendFromCart( $ca_list );
                    if ( ! $is_payment_disabled && $payment_id !== null ) {
                        $payment->setDetails( $ca_list, $coupon )->save();
                    }
                    $response = array(
                        'success' => true,
                    );
                } else {
                    $response = array(
                        'success'    => false,
                        'error_code' => 4,
                        'error'      => __( 'Pay locally is not available.', 'bookly' ),
                    );
                }
            } else {
                $response = array(
                    'success'         => false,
                    'error_code'      => 3,
                    'failed_cart_key' => $failed_cart_key,
                    'error'           => Lib\Config::showStepCart()
                        ? __( 'The highlighted time is not available anymore. Please, choose another time slot.', 'bookly' )
                        : __( 'The selected time is not available anymore. Please, choose another time slot.', 'bookly' ),
                );
            }
        } else {
            $response = array( 'success' => false, 'error_code' => 1, 'error' => __( 'Session error.', 'bookly' ) );
        }

        wp_send_json( $response );
    }

    /**
     * Save cart items as pending appointments.
     */
    public function executeSavePendingAppointment()
    {
        // List of gateways valid for this action.
        $valid_gateways = array(
            Lib\Entities\Payment::TYPE_PAYULATAM
        );

        $gateway = $this->getParameter( 'gateway' );

        if ( in_array( $gateway, $valid_gateways ) && get_option( 'ab_' . $gateway ) ) {
            $userData = new Lib\UserBookingData( $this->getParameter( 'form_id' ) );
            if ( $userData->load() ) {
                $failed_cart_key = $userData->cart->getFailedKey();
                if ( $failed_cart_key === null ) {
                    $coupon    = $userData->getCoupon();
                    if ( $coupon ) {
                        $coupon->claim();
                        $coupon->save();
                    }
                    $cart_info = $userData->cart->getInfo();
                    $payment   = new Lib\Entities\Payment();
                    $payment->set( 'type', $this->getParameter( 'gateway' ) )
                        ->set( 'status',   Lib\Entities\Payment::STATUS_PENDING )
                        ->set( 'total',    $cart_info['total_price'] )
                        ->set( 'created',  current_time( 'mysql' ) )
                        ->save();
                    $payment_id = $payment->get( 'id' );
                    $ca_list = $userData->save( $payment_id );
                    $payment->setDetails( $ca_list, $coupon )->save();
                    $response = array(
                        'success' => true,
                        'ca_ids'  => implode( ',', array_keys( $ca_list ) )
                    );
                } else {
                    $response = array(
                        'success'         => false,
                        'error_code'      => 3,
                        'failed_cart_key' => $failed_cart_key,
                        'error'           => Lib\Config::showStepCart()
                            ? __( 'The highlighted time is not available anymore. Please, choose another time slot.', 'bookly' )
                            : __( 'The selected time is not available anymore. Please, choose another time slot.', 'bookly' ),
                    );
                }
            } else {
                $response = array( 'success' => false, 'error_code' => 1, 'error' => __( 'Session error.', 'bookly' ) );
            }
        } else {
            $response = array( 'success' => false, 'error_code' => 5, 'error' => __( 'Invalid gateway.', 'bookly' ) );
        }

        wp_send_json( $response );
    }

    public function executeCheckCart()
    {
        $userData = new Lib\UserBookingData( $this->getParameter( 'form_id' ) );

        if ( $userData->load() ) {
            $failed_cart_key = $userData->cart->getFailedKey();
            if ( $failed_cart_key === null ) {
                $response = array( 'success' => true );
            } else {
                $response = array(
                    'success'         => false,
                    'error_code'      => 3,
                    'failed_cart_key' => $failed_cart_key,
                    'error'           => Lib\Config::showStepCart()
                        ? __( 'The highlighted time is not available anymore. Please, choose another time slot.', 'bookly' )
                        : __( 'The selected time is not available anymore. Please, choose another time slot.', 'bookly' )
                );
            }
        } else {
            $response = array( 'success' => false, 'error_code' => 5, 'error' => __( 'Invalid gateway.', 'bookly' ) );
        }

        wp_send_json( $response );
    }

    /**
     * Cancel Appointment using token.
     */
    public function executeCancelAppointment()
    {
        $customer_appointment = new Lib\Entities\CustomerAppointment();

        if ( $customer_appointment->loadBy( array( 'token' => $this->getParameter( 'token' ) ) ) ) {
            $minimum_time_prior_cancel = (int) get_option( 'ab_settings_minimum_time_prior_cancel', 0 );
            $allow_cancel = true;
            $appointment  = new Lib\Entities\Appointment();
            if ( $appointment->load( $customer_appointment->get( 'appointment_id' ) ) ) {
                if ( $minimum_time_prior_cancel > 0 ) {
                    $allow_cancel_time = strtotime( $appointment->get( 'start_date' ) ) - $minimum_time_prior_cancel * HOUR_IN_SECONDS;
                    if ( current_time( 'timestamp' ) > $allow_cancel_time ) {
                        $allow_cancel = false;
                    }
                }

                if ( $allow_cancel ) {
                    $customer_appointment->set( 'status', Lib\Entities\CustomerAppointment::STATUS_CANCELLED );
                    Lib\NotificationSender::send( $customer_appointment );

                    if ( get_option( 'ab_settings_client_cancel_appointment_action' ) == 'delete' ) {
                        $customer_appointment->deleteCascade( true );
                    } else {
                        if ( $customer_appointment->get( 'compound_token' ) ) {
                            do_action( 'bookly_compound_cancel_appointment', $customer_appointment );
                        } else {
                            $customer_appointment->save();
                            if ( $customer_appointment->get( 'extras' ) != '[]' ) {
                                $extras_duration = $appointment->getMaxExtrasDuration();
                                if ( $appointment->get( 'extras_duration' ) != $extras_duration ) {
                                    $appointment->set( 'extras_duration', $extras_duration );
                                    $appointment->save();
                                }
                            }
                            $appointment->handleGoogleCalendar();
                        }
                    }
                }
            }

            if ( $this->url = $allow_cancel ? get_option( 'ab_settings_cancel_page_url' ) : get_option( 'ab_settings_cancel_denied_page_url' ) ) {
                wp_redirect( $this->url );
                $this->render( 'redirection' );
                exit;
            }
        }

        $this->url = home_url();
        if ( isset ( $_SERVER['HTTP_REFERER'] ) ) {
            if ( parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_HOST ) == parse_url( $this->url, PHP_URL_HOST ) ) {
                // Redirect back if user came from our site.
                $this->url = $_SERVER['HTTP_REFERER'];
            }
        }
        wp_redirect( $this->url );
        $this->render( 'redirection' );
        exit;
    }

    /**
     * Approve appointment using token.
     */
    public function executeApproveAppointment()
    {
        $customer_appointment = new Lib\Entities\CustomerAppointment();
        // In url token is XORed.
        $token = Lib\Utils\Common::xorDecrypt( $this->getParameter( 'token' ), 'approve' );
        if ( $customer_appointment->loadBy( array( 'token' => $token ) ) ) {
            $send_notification = false;
            /** @var Lib\Entities\CustomerAppointment[] $ca_list */
            if ( $customer_appointment->get( 'compound_token' ) ) {
                $ca_list = Lib\Entities\CustomerAppointment::query()->where( 'compound_token', $customer_appointment->get( 'compound_token' ) )->find();
            } else {
                $ca_list = array( $customer_appointment );
            }
            $appointment = new Lib\Entities\Appointment();
            foreach ( $ca_list as $ca ) {
                if ( $ca->get( 'status' ) != Lib\Entities\CustomerAppointment::STATUS_APPROVED ) {
                    $ca->set( 'status', Lib\Entities\CustomerAppointment::STATUS_APPROVED )->save();
                    $appointment->load( $ca->get( 'appointment_id' ) );
                    $appointment->handleGoogleCalendar();
                    $send_notification = true;
                }
            }
            if ( $send_notification ) {
                Lib\NotificationSender::send( $customer_appointment );
            }
            if ( $this->url = get_option( 'ab_settings_approve_page_url' ) ) {
                wp_redirect( $this->url );
                $this->render( 'redirection' );
                exit;
            }
        }

        $this->url = home_url();
        if ( isset ( $_SERVER['HTTP_REFERER'] ) ) {
            if ( parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_HOST ) == parse_url( $this->url, PHP_URL_HOST ) ) {
                // Redirect back if user came from our site.
                $this->url = $_SERVER['HTTP_REFERER'];
            }
        }
        wp_redirect( $this->url );
        $this->render( 'redirection' );
        exit;
    }

    /**
     * Apply coupon
     */
    public function executeApplyCoupon()
    {
        if ( ! get_option( 'ab_settings_coupons' ) ) {
            wp_send_json_error();
        }

        $response = null;
        $userData = new Lib\UserBookingData( $this->getParameter( 'form_id' ) );

        if ( $userData->load() ) {
            $coupon_code = $this->getParameter( 'coupon' );

            $coupon = new Lib\Entities\Coupon();
            $coupon->loadBy( array(
                'code' => $coupon_code,
            ) );

            if ( $coupon->isLoaded() && $coupon->get( 'used' ) < $coupon->get( 'usage_limit' ) ) {
                $userData->fillData( array( 'coupon' => $coupon_code ) );
                $cart_info = $userData->cart->getInfo();
                $response = array(
                    'success' => true,
                    'text'    => $this->_prepareInfoText( 6, Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_info_coupon' ), $userData ),
                    'total'   => $cart_info['total_price']
                );
            } else {
                $userData->fillData( array( 'coupon' => null ) );
                $response = array(
                    'success'    => false,
                    'error_code' => 6,
                    'error'      => __( 'This coupon code is invalid or has been used', 'bookly' ),
                    'text'       => $this->_prepareInfoText( 6, Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_info_coupon' ), $userData )
                );
            }
        } else {
            $response = array( 'success' => false, 'error_code' => 1, 'error' => __( 'Session error.', 'bookly' ) );
        }

        // Output JSON response.
        wp_send_json( $response );
    }

    /**
     * Log in to WordPress in the Details step.
     */
    public function executeWpUserLogin()
    {
        /** @var \WP_User $user */
        $user = wp_signon();
        if ( is_wp_error( $user ) ) {
            wp_send_json_error( array( 'message' => __( 'Incorrect username or password.' ) ) );
        } else {
            $customer = new Lib\Entities\Customer();
            if ( $customer->loadBy( array( 'wp_user_id' => $user->ID ) ) ) {
                $user_info = array(
                    'name'  => $customer->get( 'name' ),
                    'email' => $customer->get( 'email' ),
                    'phone' => $customer->get( 'phone' )
                );
            } else {
                $user_info  = array(
                    'name'  => $user->display_name,
                    'email' => $user->user_email
                );
            }
            $userData = new Lib\UserBookingData( $this->getParameter( 'form_id' ) );
            $userData->load();
            $userData->fillData( $user_info );
            wp_send_json_success( $user_info );
        }
    }

    /**
     * Drop cart item.
     */
    public function executeCartDropItem()
    {
        $userData = new Lib\UserBookingData( $this->getParameter( 'form_id' ) );
        $total_price = 0;
        if ( $userData->load() ) {
            $cart_key       = $this->getParameter( 'cart_key' );
            $edit_cart_keys = $userData->get( 'edit_cart_keys' );

            $userData->cart->drop( $cart_key );
            if ( ( $idx = array_search( $cart_key, $edit_cart_keys) ) !== false ) {
                unset ( $edit_cart_keys[ $idx ] );
                $userData->set( 'edit_cart_keys', $edit_cart_keys );
            }

            $cart_info   = $userData->cart->getInfo();
            $total_price = $cart_info['total_price'];
            $deposit_total_price = $cart_info['total_deposit_price'];
        }
        wp_send_json_success(
            array(
                'total_price' => Lib\Utils\Common::formatPrice( $total_price ),
                'total_deposit_price' => Lib\Utils\Common::formatPrice( $deposit_total_price )
            )
        );
    }

    /**
     * Get info for IP.
     */
    public function executeIpInfo()
    {
        $curl = new Lib\Curl\Curl();
        $curl->options['CURLOPT_CONNECTTIMEOUT'] = 8;
        $curl->options['CURLOPT_TIMEOUT']        = 10;
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = '';
        }
        @header( 'Content-Type: application/json; charset=UTF-8' );
        echo $curl->get( 'http://ipinfo.io/' . $ip .'/json' );
        wp_die();
    }

    /**
     * Output a PNG image of captcha to browser.
     */
    public function executeCaptcha()
    {
        Lib\Captcha\Captcha::draw( $this->getParameter( 'form_id' ) );
    }

    public function executeCaptchaRefresh()
    {
        Lib\Captcha\Captcha::init( $this->getParameter( 'form_id' ) );
        wp_send_json_success( array( 'captcha_url' => admin_url( 'admin-ajax.php?action=ab_captcha&form_id=' . $this->getParameter( 'form_id' ) . '&' . microtime( true ) ) ) );
    }

    /**
     * Render progress tracker into a variable.
     *
     * @param int $step
     * @param Lib\UserBookingData $userData
     */
    private function _prepareProgressTracker( $step, Lib\UserBookingData $userData )
    {
        if ( get_option( 'ab_appearance_show_progress_tracker' ) ) {
            $payment_disabled = Lib\Config::isPaymentDisabled();
            if ( ! $payment_disabled && $step > 1 ) {
                $info = $userData->cart->getInfo( true );
                if ( $info['total_deposit_price'] == 0 && $step < 4 ) {
                    // Assume that payment is disabled and check chain items.
                    // If one is incomplete or its price is more than zero then the payment step should be displayed.
                    $payment_disabled = true;
                    foreach ( $userData->chain->getItems() as $item ) {
                        if ( $item->getExtrasAmount() > 0 ) {
                            $payment_disabled = false;
                            break;
                        } else {
                            if ( $item->getService()->get( 'type' ) == Lib\Entities\Service::TYPE_SIMPLE ) {
                                $staff_ids = $item->get( 'staff_ids' );
                                $staff     = null;
                                if ( count( $staff_ids ) == 1 ) {
                                    $staff = Lib\Entities\Staff::find( $staff_ids[0] );
                                }
                                if ( $staff ) {
                                    $staff_service = new Lib\Entities\StaffService();
                                    $staff_service->loadBy( array(
                                        'staff_id'   => $staff->get( 'id' ),
                                        'service_id' => $item->getService()->get( 'id' ),
                                    ) );
                                    if ( $staff_service->get( 'price' ) > 0 ) {
                                        $payment_disabled = false;
                                        break;
                                    }
                                } else {
                                    $payment_disabled = false;
                                    break;
                                }
                            } else {    // Service::TYPE_COMPOUND
                                if ( $item->getService()->get( 'price' ) > 0 ) {
                                    $payment_disabled = false;
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            $this->progress_tracker = $this->render( '_progress_tracker', array(
                'step' => $step,
                'show_cart' => Lib\Config::showStepCart(),
                'payment_disabled' => $payment_disabled,
                'skip_service_step' => Lib\Session::hasFormVar( $this->getParameter( 'form_id' ), 'attrs' )
            ), false );
        } else {
            $this->progress_tracker = '';
        }
    }

    /**
     * Render info text into a variable.
     *
     * @param integer             $step
     * @param string              $text
     * @param Lib\UserBookingData $userData
     * @return string
     */
    private function _prepareInfoText( $step, $text, $userData )
    {
        if ( empty ( $this->info_text_codes ) ) {
            if ( $step == 1 ) {
                // No replacements.
            } elseif ( $step < 4 ) {
                $data = array(
                    'category_names'      => array(),
                    'numbers_of_persons'  => array(),
                    'service_dates'       => array(),
                    'service_info'        => array(),
                    'service_names'       => array(),
                    'service_prices'      => array(),
                    'service_times'       => array(),
                    'staff_info'          => array(),
                    'staff_names'         => array(),
                    'total_price'         => 0,
                    'total_deposit_price' => 0,
                );
                /** @var Lib\ChainItem $chain_item */
                foreach ( $userData->chain->getItems() as $chain_item ) {
                    $data['numbers_of_persons'][] = $chain_item->get( 'number_of_persons' );
                    /** @var Lib\Entities\Service $service */
                    $service = Lib\Entities\Service::find( $chain_item->get( 'service_id' ) );
                    $data['service_names'][]  = $service->getTitle();
                    $data['service_info'][]   = $service->getInfo();
                    $data['category_names'][] = $service->getCategoryName();
                    /** @var Lib\Entities\Staff $staff */
                    $staff     = null;
                    $staff_ids = $chain_item->get( 'staff_ids' );
                    if ( count( $staff_ids ) == 1 ) {
                        $staff = Lib\Entities\Staff::find( $staff_ids[0] );
                    }
                    if ( $staff ) {
                        $data['staff_names'][] = $staff->getName();
                        $data['staff_info'][]  = $staff->getInfo();
                        if ( $service->get( 'type' ) == Lib\Entities\Service::TYPE_COMPOUND ) {
                            $price = $service->get( 'price' );
                            $deposit_price = $price;
                        } else {
                            $staff_service = new Lib\Entities\StaffService();
                            $staff_service->loadBy( array(
                                'staff_id'   => $staff->get( 'id' ),
                                'service_id' => $service->get( 'id' ),
                            ) );
                            $price = $staff_service->get( 'price' );
                            $deposit_price = apply_filters( 'bookly_get_deposit_amount', $price, $staff_service->get( 'deposit' ) );
                        }
                    } else {
                        $data['staff_names'][] = __( 'Any', 'bookly' );
                        $price = false;
                        $deposit_price = false;
                    }
                    $data['service_prices'][] = $price !== false ? Lib\Utils\Common::formatPrice( $price ) : '-';
                    $data['total_price'] += $price * $chain_item->get( 'number_of_persons' );
                    $data['total_deposit_price'] += $deposit_price * $chain_item->get( 'number_of_persons' );

                    $data = apply_filters( 'bookly_prepare_chain_item_info_text', $data, $chain_item );
                }

                $this->info_text_codes = array(
                    '[[CATEGORY_NAME]]'     => '<b>' . implode( ', ', $data['category_names'] ) . '</b>',
                    '[[LOGIN_FORM]]'        => ( get_current_user_id() == 0 ) ? $this->render( '_login_form', array(), false ) : '',
                    '[[NUMBER_OF_PERSONS]]' => '<b>' . implode( ', ', $data['numbers_of_persons'] ) . '</b>',
                    '[[SERVICE_DATE]]'      => '<b>' . implode( ', ', $data['service_dates'] ) . '</b>',
                    '[[SERVICE_INFO]]'      => '<b>' . implode( ', ', $data['service_info'] ) . '</b>',
                    '[[SERVICE_NAME]]'      => '<b>' . implode( ', ', $data['service_names'] ) . '</b>',
                    '[[SERVICE_PRICE]]'     => '<b>' . implode( ', ', $data['service_prices'] ) . '</b>',
                    '[[SERVICE_TIME]]'      => '<b>' . implode( ', ', $data['service_times'] ) . '</b>',
                    '[[STAFF_INFO]]'        => '<b>' . implode( ', ', $data['staff_info'] ) . '</b>',
                    '[[STAFF_NAME]]'        => '<b>' . implode( ', ', $data['staff_names'] ) . '</b>',
                    '[[TOTAL_PRICE]]'       => '<b>' . Lib\Utils\Common::formatPrice( $data['total_price'] ) . '</b>',
                    '[[AMOUNT_TO_PAY]]'     => '<b>' . Lib\Utils\Common::formatPrice( $data['total_deposit_price'] ) . '</b>',
                    '[[AMOUNT_DUE]]'        => '<b>' . Lib\Utils\Common::formatPrice( $data['total_price'] - $data['total_deposit_price'] ) . '</b>',
                );
                $this->info_text_codes      = apply_filters( 'bookly_prepare_info_text_code', $this->info_text_codes, $data );
            } else {
                $data = array(
                    'service'           => array(),
                    'service_name'      => array(),
                    'category_name'     => array(),
                    'staff_name'        => array(),
                    'staff_info'        => array(),
                    'booking_number'    => $userData->getBookingNumbers(),
                    'service_info'      => array(),
                    'service_date'      => array(),
                    'service_price'     => array(),
                    'extras'            => array(),
                    'number_of_persons' => array(),
                );
                /** @var Lib\CartItem $cart_item */
                foreach ( $userData->cart->getItems() as $cart_key => $cart_item ) {
                    $service = $cart_item->getService();
                    $slot    = $cart_item->get( 'slots' );
                    $service_datetime = date( 'Y-m-d H:i:s', $slot[0][2] );
                    if ( get_option( 'ab_settings_use_client_time_zone' ) ) {
                        $service_datetime = Lib\Utils\DateTime::applyTimeZoneOffset( $service_datetime, $userData->get( 'time_zone_offset' ) );
                    }
                    $data['category_name'][] = $service->getCategoryName();
                    $data['staff_name'][]    = $cart_item->getStaff()->getName();
                    $data['staff_info'][]    = $cart_item->getStaff()->getInfo();
                    $data['service_info'][]  = $service->getInfo();
                    $data['service_name'][]  = $service->getTitle();
                    $data['service_date'][]  = Lib\Utils\DateTime::formatDate( $service_datetime );
                    $data['service_price'][] = Lib\Utils\Common::formatPrice( $cart_item->getServicePrice() );
                    $data['service_time'][]  = Lib\Utils\DateTime::formatTime( $service_datetime );
                    $data['number_of_persons'][] = $cart_item->get( 'number_of_persons' );
                    $data['deposit_amount'][] = $cart_item->getDepositPrice( true );
                    $data['deposit_amount_due'][] = Lib\Utils\Common::formatPrice( $cart_item->getAmountDue() );

                    $data = apply_filters( 'bookly_prepare_cart_item_info_text', $data, $cart_item );
                }

                $cart_info = $userData->cart->getInfo( $step >= 6 );  // >= step payment

                $this->info_text_codes  = array(
                    '[[STAFF_NAME]]'        => '<b>' . implode( ', ', $data['staff_name'] ) . '</b>',
                    '[[STAFF_INFO]]'        => '<b>' . implode( ', ', $data['staff_info'] ) . '</b>',
                    '[[BOOKING_NUMBER]]'    => '<b>' . implode( ', ', $data['booking_number'] ) . '</b>',
                    '[[SERVICE_NAME]]'      => '<b>' . implode( ', ', $data['service_name'] ) . '</b>',
                    '[[SERVICE_INFO]]'      => '<b>' . implode( ', ', $data['service_info'] ) . '</b>',
                    '[[CATEGORY_NAME]]'     => '<b>' . implode( ', ', $data['category_name'] ) . '</b>',
                    '[[NUMBER_OF_PERSONS]]' => '<b>' . implode( ', ', $data['number_of_persons'] ) . '</b>',
                    '[[SERVICE_TIME]]'      => '<b>' . implode( ', ', $data['service_time'] ) . '</b>',
                    '[[SERVICE_DATE]]'      => '<b>' . implode( ', ', $data['service_date'] ) . '</b>',
                    '[[SERVICE_PRICE]]'     => '<b>' . implode( ', ', $data['service_price'] ) . '</b>',
                    '[[TOTAL_PRICE]]'       => '<b>' . Lib\Utils\Common::formatPrice( $cart_info['total_price'] ) . '</b>',
                    '[[LOGIN_FORM]]'        => ( get_current_user_id() == 0 ) ? $this->render( '_login_form', array(), false ) : '',
                    '[[AMOUNT_TO_PAY]]'     => '<b>' . Lib\Utils\Common::formatPrice( $cart_info['total_deposit_price'] ) . '</b>',
                    '[[AMOUNT_DUE]]'        => '<b>' . Lib\Utils\Common::formatPrice( $cart_info['total_due'] ) . '</b>',
                );
                $this->info_text_codes      = apply_filters( 'bookly_prepare_info_text_code', $this->info_text_codes, $data );
            }
        }

        return strtr( nl2br( $text ), $this->info_text_codes );
    }

    /**
     * Check if cart button should be shown.
     *
     * @param Lib\UserBookingData $userData
     * @return bool
     */
    private function _showCartButton( Lib\UserBookingData $userData )
    {
        if ( Lib\Config::showStepCart() ) {
            if ( count( $userData->cart->getItems() ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Add data for the skipped Service step.
     *
     * @param Lib\UserBookingData $userData
     */
    private function _setDataForSkippedServiceStep( Lib\UserBookingData $userData )
    {
        // Staff ids.
        $attrs = Lib\Session::getFormVar( $this->getParameter( 'form_id' ), 'attrs' );
        if ( $attrs['staff_member_id'] == 0 ) {
            $staff_ids = array_map( function ( $staff ) { return $staff['id']; }, Lib\Entities\StaffService::query()
                ->select( 'staff_id AS id' )
                ->where( 'service_id', $attrs['service_id'] )
                ->fetchArray()
            );
        } else {
            $staff_ids = array( $attrs['staff_member_id'] );
        }
        // Date.
        $date_from = date_create( '@' . current_time( 'timestamp' ) )->modify( '+' . Lib\Config::getMinimumTimePriorBooking() . ' sec' );
        if ( get_option( 'ab_settings_use_client_time_zone' ) ) {
            $time_zone_offset = $this->getParameter( 'time_zone_offset' );
            $userData->set( 'time_zone_offset', $time_zone_offset );
            $date_from->modify( '-' . ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS + $time_zone_offset * MINUTE_IN_SECONDS ) . ' sec' );
        }
        // Days and times.
        $days_times = Lib\Config::getDaysAndTimes( $userData->get( 'time_zone_offset' ) );
        $time_from  = key( $days_times['times'] );
        end( $days_times['times'] );

        $userData->chain->clear();
        $chain_item = new Lib\ChainItem();
        $chain_item->set( 'service_id', $attrs['service_id'] );
        $chain_item->set( 'staff_ids', $staff_ids );
        $chain_item->set( 'number_of_persons', 1 );
        $chain_item->set( 'quantity', 1 );
        $userData->chain->add( $chain_item );

        $userData->fillData( array(
            'date_from'      => $date_from->format( 'Y-m-d' ),
            'days'           => array_keys( $days_times['days'] ),
            'time_from'      => $time_from,
            'time_to'        => key( $days_times['times'] ),
            'slots'          => array(),
            'edit_cart_keys' => array(),
        ) );
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