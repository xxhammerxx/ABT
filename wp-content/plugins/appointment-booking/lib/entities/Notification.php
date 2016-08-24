<?php
namespace Bookly\Lib\Entities;

use Bookly\Lib;

/**
 * Class Notification
 * @package Bookly\Lib\Entities
 */
class Notification extends Lib\Base\Entity
{
    protected static $table = 'ab_notifications';

    protected static $schema = array(
        'id'      => array( 'format' => '%d' ),
        'gateway' => array( 'format' => '%s', 'default' => 'email' ),
        'type'    => array( 'format' => '%s', 'default' => '' ),
        'active'  => array( 'format' => '%d', 'default' => 0 ),
        'copy'    => array( 'format' => '%d', 'default' => 0 ),
        'subject' => array( 'format' => '%s', 'default' => '' ),
        'message' => array( 'format' => '%s', 'default' => '' ),
    );

    protected static $cache = array();

    public function save()
    {
        $return = parent::save();
        if ( $this->isLoaded() ) {
            // Register string for translate in WPML.
            do_action( 'wpml_register_single_string', 'bookly', $this->get( 'gateway' ) . '_' . $this->get( 'type' ), $this->get( 'message' ) );
            if ( $this->get( 'gateway' ) == 'email' ) {
                do_action( 'wpml_register_single_string', 'bookly', $this->get( 'gateway' ) . '_' . $this->get( 'type' ) . '_subject', $this->get( 'subject' ) );
            }
        }

        return $return;
    }

}