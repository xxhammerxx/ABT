<?php
namespace Bookly\Lib;

/**
 * Class CartItem
 * @package Bookly\Lib
 */
class CartItem
{
    private $data = array(
        // Step service
        'service_id'        => null,
        'staff_ids'         => null,
        'number_of_persons' => null,
        'date_from'         => null,
        'days'              => null,
        'time_from'         => null,
        'time_to'           => null,
        // Step extras
        'extras'            => array(),
        // Step time
        'slots'             => null,
        // Step details
        'custom_fields'     => array(),
    );

    public static $service_prices = array();

    /**
     * Constructor.
     */
    public function __construct() { }

    /**
     * Get data parameter.
     *
     * @param string $name
     * @return mixed
     */
    public function get( $name )
    {
        if ( array_key_exists( $name, $this->data ) ) {
            return $this->data[ $name ];
        }

        return false;
    }

    /**
     * Set data parameter.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function set( $name, $value )
    {
        $this->data[ $name ] = $value;
    }

    /**
     * Get data.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set data.
     *
     * @param array $data
     */
    public function setData( array $data )
    {
        $this->data = $data;
    }

    /**
     * Get service.
     *
     * @return Entities\Service
     */
    public function getService()
    {
        return Entities\Service::find( $this->data['service_id'] );
    }

    /**
     * Get service price.
     *
     * @return float
     */
    public function getServicePrice()
    {
        $service = $this->getService();
        $price   = 0.0;
        if ( $service->get( 'type' ) == Entities\Service::TYPE_COMPOUND ) {
            $price += $service->get( 'price' );
        } else {
            $slots  = $this->get( 'slots' );
            list ( $service_id, $staff_id ) = $slots[0];
            if ( ! isset ( self::$service_prices[ $staff_id ][ $service_id ] ) ) {
                $staff_service = new Entities\StaffService();
                $staff_service->loadBy( array(
                    'staff_id'   => $staff_id,
                    'service_id' => $service_id,
                ) );
                self::$service_prices[ $staff_id ][ $service_id ] = $staff_service->get( 'price' );
            }
            $price += self::$service_prices[ $staff_id ][ $service_id ];
        }

        return $price + $this->getExtrasAmount();
    }

    /**
     * Get service deposit.
     *
     * @return mixed
     */
    public function getDeposit()
    {
        $slots = $this->get( 'slots' );
        list ( $service_id, $staff_id ) = $slots[0];
        $staff_service = new Entities\StaffService();
        $staff_service->loadBy( array(
            'staff_id'   => $staff_id,
            'service_id' => $service_id,
        ) );
        return $staff_service->get( 'deposit' );
    }

    /**
     * Get service deposit price.
     *
     * @param bool $format
     *
     * @return mixed
     */
    public function getDepositPrice( $format = false )
    {
        $price = $this->getServicePrice();
        $deposit = $this->getDeposit();
        $result = apply_filters( 'bookly_get_deposit_amount', $price, $deposit, $format );
        return $result;
    }

    /**
     * Get service deposit price formatted.
     *
     * @return mixed
     */
    public function getAmountDue()
    {
        $price = $this->getServicePrice();
        $deposit = $this->getDepositPrice();
        return $price - $deposit;
    }

    /**
     * Get staff.
     *
     * @return Entities\Staff
     */
    public function getStaff()
    {
        $slots = $this->get( 'slots' );
        $staff_id = $slots[0][1];

        return Entities\Staff::find( $staff_id );
    }

    /**
     * Get summary price of service's extras.
     *
     * @return int
     */
    public function getExtrasAmount()
    {
        $amount = 0.0;
        $_extras = $this->get( 'extras' );
        /** @var \BooklyServiceExtras\Lib\Entities\ServiceExtra[] $extras */
        $extras = apply_filters( 'bookly_extras_find_by_ids', array(), array_keys( $_extras ) );
        foreach ( $extras as $extra ) {
            $amount += $extra->get( 'price' ) * $_extras[ $extra->get( 'id' ) ];
        }
        return $amount;
    }

    /**
     * Get duration of service's extras.
     *
     * @return int
     */
    public function getExtrasDuration()
    {
        return apply_filters( 'bookly_extras_get_total_duration', 0, $this->get( 'extras' ) );
    }

    public function isFirstSubService( $service_id )
    {
        return $this->data['slots'][0][0] == $service_id;
    }

}