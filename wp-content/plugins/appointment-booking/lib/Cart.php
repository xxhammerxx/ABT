<?php
namespace Bookly\Lib;

/**
 * Class Cart
 * @package Bookly\Lib
 */
class Cart
{
    /**
     * @var CartItem[]
     */
    private $items = array();

    /**
     * @var UserBookingData
     */
    private $userData = null;

    /**
     * Constructor.
     *
     * @param UserBookingData $userData
     */
    public function __construct( UserBookingData $userData )
    {
        $this->userData = $userData;
    }

    /**
     * Get cart item.
     *
     * @param integer $key
     * @return CartItem|false
     */
    public function get( $key )
    {
        if ( isset ( $this->items[ $key ] ) ) {
            return $this->items[ $key ];
        }

        return false;
    }

    /**
     * Add cart item.
     *
     * @param CartItem $item
     * @return integer
     */
    public function add( CartItem $item )
    {
        $this->items[] = $item;
        end( $this->items );

        return key( $this->items );
    }

    /**
     * Replace given item with other items.
     *
     * @param integer $key
     * @param CartItem[] $items
     * @return array
     */
    public function replace( $key, array $items )
    {
        $new_items = array();
        $new_keys  = array();
        $new_key   = 0;
        foreach ( $this->items as $cart_key => $cart_item ) {
            if ( $cart_key == $key ) {
                foreach ( $items as $item ) {
                    $new_items[ $new_key ] = $item;
                    $new_keys[] = $new_key;
                    ++ $new_key;
                }
            } else {
                $new_items[ $new_key ++ ] = $cart_item;
            }
        }
        $this->items = $new_items;

        return $new_keys;
    }

    /**
     * Drop cart item.
     *
     * @param integer $key
     */
    public function drop( $key )
    {
        unset ( $this->items[ $key ] );
    }

    /**
     * Get cart items.
     *
     * @return CartItem[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Get items data as array.
     *
     * @return array
     */
    public function getItemsData()
    {
        $data = array();
        foreach ( $this->items as $key => $item ) {
            $data[ $key ] = $item->getData();
        }

        return $data;
    }

    /**
     * Set items data from array.
     *
     * @param array $data
     */
    public function setItemsData( array $data )
    {
        foreach ( $data as $key => $item_data ) {
            $item = new CartItem();
            $item->setData( $item_data );
            $this->items[ $key ] = $item;
        }
    }

    /**
     * Save all cart items (customer appointments).
     *
     * @param Entities\Customer $customer
     * @param                   $payment_id
     * @param                   $time_zone_offset
     * @param                   $booking_numbers
     * @return Entities\CustomerAppointment[]
     */
    public function save( Entities\Customer $customer, $payment_id, $time_zone_offset, &$booking_numbers )
    {
        $ca_list = array();
        foreach ( $this->getItems() as $cart_item ) {
            $ca_entity = null;
            $service   = $cart_item->getService();
            $compound_service_id = null;
            $compound_token      = null;
            if ( $service->get( 'type' ) == Entities\Service::TYPE_COMPOUND ) {
                $compound_service_id = $service->get( 'id' );
                $compound_token      = Entities\CustomerAppointment::generateToken( 'compound_token' );
            }

            $extras = json_encode( $cart_item->get( 'extras' ) );
            $custom_fields = json_encode( $cart_item->get( 'custom_fields' ) );
            foreach ( $cart_item->get( 'slots' ) as $slot ) {
                list ( $service_id, $staff_id, $timestamp ) = $slot;
                $service = Entities\Service::find( $service_id );

                /*
                 * Get appointment with the same params.
                 * If it exists -> create connection to this appointment,
                 * otherwise create appointment and connect customer to new appointment
                 */
                $appointment = new Entities\Appointment();
                $appointment->loadBy( array(
                    'service_id' => $service_id,
                    'staff_id'   => $staff_id,
                    'start_date' => date( 'Y-m-d H:i:s', $timestamp ),
                ) );
                if ( $appointment->isLoaded() == false ) {
                    $appointment->set( 'service_id', $service_id );
                    $appointment->set( 'staff_id',   $staff_id );
                    $appointment->set( 'start_date', date( 'Y-m-d H:i:s', $timestamp ) );
                    $appointment->set( 'end_date',   date( 'Y-m-d H:i:s', $timestamp + $service->get( 'duration' ) ) );
                    $appointment->save();
                }

                // Create CustomerAppointment record.
                $customer_appointment = new Entities\CustomerAppointment();
                $customer_appointment->set( 'customer_id',         $customer->get( 'id' ) );
                $customer_appointment->set( 'appointment_id',      $appointment->get( 'id' ) );
                $customer_appointment->set( 'payment_id',          $payment_id );
                $customer_appointment->set( 'number_of_persons',   $cart_item->get( 'number_of_persons' ) );
                $customer_appointment->set( 'extras',              $extras );
                $customer_appointment->set( 'custom_fields',       $custom_fields );
                $customer_appointment->set( 'status',              get_option( 'ab_settings_default_appointment_status' ) );
                $customer_appointment->set( 'time_zone_offset',    $time_zone_offset );
                $customer_appointment->set( 'compound_service_id', $compound_service_id );
                $customer_appointment->set( 'compound_token',      $compound_token );
                $customer_appointment->save();

                // Handle extras duration.
                if ( Config::extrasEnabled() ) {
                    $appointment->set( 'extras_duration', $appointment->getMaxExtrasDuration() );
                    $appointment->save();
                }

                // Google Calendar.
                $appointment->handleGoogleCalendar();

                // Add booking number.
                $booking_numbers[] = $appointment->get( 'id' );

                if ( $ca_entity === null ) {
                    $ca_entity = $customer_appointment;
                    $ca_list[ $customer_appointment->get( 'id' ) ] = $customer_appointment;
                }
                // Only firs service have custom fields, extras (compound).
                $custom_fields = $extras = '[]';
            }
        }
        $booking_numbers = array_unique( $booking_numbers );

        return $ca_list;
    }

    /**
     * Get total price and prices for each appointment.
     *
     * @param bool $apply_coupon
     * @return array
     */
    public function getInfo( $apply_coupon = true )
    {
        $info = array( 'total_price' => 0, 'total_deposit_price' => 0, 'items' => array() );
        foreach ( $this->items as $key => $item ) {
            $info['items'][ $key ] = array(
                'total_price' => $item->getServicePrice() * $item->get( 'number_of_persons' ),
                'total_deposit_price' => $item->getDepositPrice() * $item->get( 'number_of_persons' ),
            );
            $info['total_price'] += $info['items'][ $key ]['total_price'];
            $info['total_deposit_price'] += $info['items'][ $key ]['total_deposit_price'];
        }
        $discount_total = $info['total_price'];
        $deposit_discount_total = $info['total_deposit_price'];
        if ( $apply_coupon ) {
            // Apply coupon.
            $coupon = $this->userData->getCoupon();
            if ( $coupon ) {
                $discount_total = $coupon->apply( $info['total_price'] );
                $deposit_discount_total = $coupon->apply( $info['total_deposit_price'] );
                if ( $discount_total < 0 ) {
                    $discount_total = 0;
                }
                if ( $deposit_discount_total < 0 ) {
                    $deposit_discount_total = 0;
                }
            }
        }

        // Apply discount
        if ( $discount_total != 0 ) {
            $ratio = $info['total_price'] / $discount_total;
        }
        if ( $deposit_discount_total != 0 ) {
            $deposit_ratio = $info['total_deposit_price'] / $deposit_discount_total;
        }
        foreach ( $info['items'] as &$cart_item ) {
            $cart_item['total_price'] = $discount_total != 0 ? round( $cart_item['total_price'] / $ratio, 2 ) : 0;
            $cart_item['total_deposit_price'] = $deposit_discount_total != 0 ? round( $cart_item['total_deposit_price'] / $deposit_ratio, 2 ) : 0;
        }
        $info['total_price'] = $discount_total;
        $info['total_deposit_price'] = $deposit_discount_total;
        $info['total_due'] = $info['total_price'] - $info['total_deposit_price'];

        if ( ! Config::depositEnabled() ) {
            $info['total_deposit_price'] = $info['total_price'];
            $info['total_due'] = 0;
        }

        if( $info['total_due'] < 0 ){
            $info['total_due'] = 0;
        }


        // Array like [ 'total_price' => 100,
        //              'items' => [ '3' => [ 'total_price' => 70 ],
        //                           '5' => [ 'total_price' => 30 ] ] ]
        // where 3 and 5 is cart_key of item in cart.
        return $info;
    }

    /**
     * Generate title of cart items (used in payments).
     *
     * @param int  $max_length
     * @param bool $multi_byte
     * @return string
     */
    public function getItemsTitle( $max_length = 255, $multi_byte = true )
    {
        reset( $this->items );
        $title = $this->get( key( $this->items ) )->getService()->getTitle();
        $tail  = '';
        $more  = count( $this->items ) - 1;
        if ( $more > 0 ) {
            $tail = sprintf( _n( ' and %d more item', ' and %d more items', $more, 'bookly' ), $more );
        }

        if ( $multi_byte ) {
            if ( preg_match_all( '/./su', $title . $tail, $matches ) > $max_length ) {
                $length_tail = preg_match_all( '/./su', $tail, $matches );
                $title       = preg_replace( '/^(.{' . ( $max_length - $length_tail - 3 ) . '}).*/su', '$1', $title ) . '...';
            }
        } else {
            if ( strlen( $title . $tail ) > $max_length ) {
                while ( strlen( $title . $tail ) + 3 > $max_length ) {
                    $title = preg_replace( '/.$/su', '', $title );
                }
                $title .= '...';
            }
        }

        return $title . $tail;
    }

    /**
     * Return cart_key for not available appointment or NULL.
     *
     * @return int|null
     */
    public function getFailedKey()
    {
        $max_date  = date_create( '@' . ( current_time( 'timestamp' ) + Config::getMaximumAvailableDaysForBooking() * DAY_IN_SECONDS ) )->setTime( 0, 0 );

        foreach ( $this->items as $cart_key => $cart_item ) {
            $service     = $cart_item->getService();
            $is_compound = $service->get( 'type' ) == Entities\Service::TYPE_COMPOUND;
            foreach ( $cart_item->get( 'slots' ) as $slot ) {
                list ( $service_id, $staff_id, $timestamp ) = $slot;
                if ( $is_compound ) {
                    $service = Entities\Service::find( $service_id );
                }
                $bound_beg = date_create( '@' . $timestamp )->modify( '-' . (int) $service->get( 'padding_left' ) . ' sec' );
                $bound_end = date_create( '@' . $timestamp )->modify( ( (int) $service->get( 'duration' ) + (int) $service->get( 'padding_right' ) + $cart_item->getExtrasDuration() ) . ' sec' );

                if ( $bound_end < $max_date ) {
                    $query = Entities\CustomerAppointment::query( 'ca' )
                        ->select( 'ss.capacity, SUM(ca.number_of_persons) AS total_number_of_persons,
                            DATE_SUB(a.start_date, INTERVAL (COALESCE(s.padding_left,0) ) SECOND) AS bound_left,
                            DATE_ADD(a.end_date,   INTERVAL (COALESCE(s.padding_right,0) + a.extras_duration ) SECOND) AS bound_right' )
                        ->leftJoin( 'Appointment', 'a', 'a.id = ca.appointment_id' )
                        ->leftJoin( 'StaffService', 'ss', 'ss.staff_id = a.staff_id AND ss.service_id = a.service_id' )
                        ->leftJoin( 'Service', 's', 's.id = a.service_id' )
                        ->where( 'a.staff_id', $staff_id )
                        ->whereNot( 'ca.status', Entities\CustomerAppointment::STATUS_CANCELLED )
                        ->groupBy( 'a.service_id, a.start_date' )
                        ->havingRaw( '%s > bound_left AND bound_right > %s AND ( total_number_of_persons + %d ) > ss.capacity',
                            array( $bound_end->format( 'Y-m-d H:i:s' ), $bound_beg->format( 'Y-m-d H:i:s' ), $cart_item->get( 'number_of_persons' ) ) )
                        ->limit( 1 );
                    $rows = $query->execute( Query::HYDRATE_NONE );

                    if ( $rows != 0 ) {
                        // Exist intersect appointment, time not available.
                        return $cart_key;
                    }
                }
            }
        }

        return null;
    }

}