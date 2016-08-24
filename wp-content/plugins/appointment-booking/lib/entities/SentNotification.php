<?php
namespace Bookly\Lib\Entities;

use Bookly\Lib;

/**
 * Class SentNotification
 * @package Bookly\Lib\Entities
 */
class SentNotification extends Lib\Base\Entity
{
    protected static $table = 'ab_sent_notifications';

    protected static $schema = array(
        'id'                      => array( 'format' => '%d' ),
        'customer_appointment_id' => array( 'format' => '%d', 'reference' => array( 'entity' => 'CustomerAppointment' ) ),
        'staff_id'                => array( 'format' => '%d', 'reference' => array( 'entity' => 'Staff' ) ),
        'gateway'                 => array( 'format' => '%s', 'default' => 'email' ),
        'type'                    => array( 'format' => '%s' ),
        'created'                 => array( 'format' => '%s' ),
    );

    protected static $cache = array();

}