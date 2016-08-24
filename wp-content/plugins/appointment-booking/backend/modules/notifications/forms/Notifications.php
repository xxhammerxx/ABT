<?php
namespace Bookly\Backend\Modules\Notifications\Forms;

use Bookly\Lib;

/**
 * Class Notifications
 * @package Bookly\Backend\Modules\Notifications\Forms
 */
class Notifications extends Lib\Base\Form
{
    public $types = array(
        'single' => array(
            'client_pending_appointment',
            'staff_pending_appointment',
            'client_approved_appointment',
            'staff_approved_appointment',
            'client_cancelled_appointment',
            'staff_cancelled_appointment',
            'client_new_wp_user',
            'client_reminder',
            'client_follow_up',
            'staff_agenda',
        ),
        'combined' => array(
            'client_pending_appointment_cart',
            'client_approved_appointment_cart',
        ),
    );

    public $gateway;

    /**
     * Constructor.
     *
     * @param string $gateway
     */
    public function __construct( $gateway = 'email' )
    {
        /*
         * make Visual Mode as default (instead of Text Mode)
         * allowed: tinymce - Visual Mode, html - Text Mode, test - no one Mode selected
         */
        add_filter( 'wp_default_editor', create_function( '', 'return \'tinymce\';' ) );
        $this->gateway = $gateway;
        if ( ! Lib\Config::areCombinedNotificationsEnabled() ) {
            $this->types['combined'] = array();
        }
        $this->setFields( array( 'active', 'subject', 'message', 'copy', ) );
        $this->load();
    }

    public function bind( array $_post = array(), array $files = array() )
    {
        foreach ( $this->types as $group ) {
            foreach ( $group as $type ) {
                foreach ( $this->fields as $field ) {
                    if ( isset ( $_post[ $type ] [ $field ] ) ) {
                        $this->data[ $type ][ $field ] = $_post[ $type ][ $field ];
                    }
                }
            }
        }
    }

    /**
     * Save form.
     *
     * @return bool|void
     */
    public function save()
    {
        foreach ( $this->types as $group ) {
            foreach ( $group as $type ) {
                $object = new Lib\Entities\Notification();
                $object->loadBy( array( 'type' => $type, 'gateway' => $this->gateway ) );
                $object->setFields( $this->data[ $type ] );
                $object->save();
            }
        }
    }

    public function load()
    {
        foreach ( $this->types as $group ) {
            foreach ( $group as $type ) {
                $object = new Lib\Entities\Notification();
                $object->loadBy( array( 'type' => $type, 'gateway' => $this->gateway ) );
                $this->data[ $type ]['active']  = $object->get( 'active' );
                $this->data[ $type ]['subject'] = $object->get( 'subject' );
                $this->data[ $type ]['message'] = $object->get( 'message' );
                $this->data[ $type ]['name']    = $this->getNotificationName( $type );
                $this->data[ $type ]['copy']    = $object->get( 'copy' );
            }
        }
    }

    /**
     * @param $type
     * @return mixed
     */
    public function getNotificationName ( $type )
    {
        switch ( $type ) {
            case 'client_pending_appointment':       return __( 'Notification to customer about pending appointment', 'bookly' );
            case 'client_pending_appointment_cart':  return __( 'Notification to customer about pending appointments', 'bookly' );
            case 'staff_pending_appointment':        return __( 'Notification to staff member about pending appointment', 'bookly' );
            case 'client_approved_appointment':      return __( 'Notification to customer about approved appointment', 'bookly' );
            case 'client_approved_appointment_cart': return __( 'Notification to customer about approved appointments', 'bookly' );
            case 'staff_approved_appointment':       return __( 'Notification to staff member about approved appointment', 'bookly' );
            case 'client_cancelled_appointment':     return __( 'Notification to customer about cancelled appointment', 'bookly' );
            case 'staff_cancelled_appointment':      return __( 'Notification to staff member about cancelled appointment', 'bookly' );
            case 'client_new_wp_user':               return __( 'Notification to customer about their WordPress user login details', 'bookly' );
            case 'client_reminder':                  return __( 'Evening reminder to customer about next day appointment (requires cron setup)', 'bookly' );
            case 'client_follow_up':                 return __( 'Follow-up message in the same day after appointment (requires cron setup)', 'bookly' );
            case 'staff_agenda':                     return __( 'Evening notification with the next day agenda to staff member (requires cron setup)', 'bookly' );
        }
    }

    /**
     * Render subject.
     *
     * @param string $type
     */
    public function renderSubject( $type )
    {
        printf(
            '<div class="form-group">
                <label for="%1$s">%2$s</label>
                <input type="text" class="form-control" id="%1$s" name="%3$s" value="%4$s" />
            </div>',
            $type . '_subject',
            __( 'Subject', 'bookly' ),
            $type . '[subject]',
            esc_attr( $this->data[ $type ]['subject'] )
        );
    }

    /**
     * Render message editor.
     *
     * @param string $type
     */
    public function renderEditor( $type )
    {
        $id    = $type . '_message';
        $name  = $type . '[message]';
        $value = $this->data[ $type ]['message'];

        if ( $this->gateway == 'sms' ) {
            printf(
                '<div class="form-group">
                    <label for="%1$s">%2$s</label>
                    <textarea rows="6" id="%1$s" name="%3$s" class="form-control">%4$s</textarea>
                </div>',
                $id,
                __( 'Message', 'bookly' ),
                $name,
                esc_textarea( $value )
            );
        } else {
            $settings = array(
                'textarea_name' => $name,
                'media_buttons' => false,
                'editor_height' => 384,
                'tinymce'       => array(
                    'theme_advanced_buttons1' => 'formatselect,|,bold,italic,underline,|,'.
                                                 'bullist,blockquote,|,justifyleft,justifycenter'.
                                                 ',justifyright,justifyfull,|,link,unlink,|'.
                                                 ',spellchecker,wp_fullscreen,wp_adv'
                )
            );

            echo '<div class="form-group">
                <label>' . __( 'Message', 'bookly' ) . '</label>';
            wp_editor( $value, $id, $settings );
            echo '</div>';
        }
    }

    /**
     * Render copy.
     *
     * @param string $type
     */
    public function renderCopy( $type )
    {
        if ( in_array( $type, array( 'staff_pending_appointment', 'staff_approved_appointment', 'staff_cancelled_appointment' ) ) ) {
            printf(
                '<div class="form-group">
                    <input name="%1$s" type="hidden" value="0">
                    <div class="checkbox"><label for="%2$s"><input id="%2$s" name="%1$s" type="checkbox" value="1" %3$s> %4$s</label></div>
                </div>',
                $type . '[copy]',
                $type . '_copy',
                checked( $this->data[ $type ]['copy'], true, false ),
                __( 'Send copy to administrators', 'bookly' )
            );
        }
    }

    /**
     * Render sending time.
     *
     * @param string $type
     */
    public function renderSendingTime( $type )
    {
        if ( in_array( $type, array( 'staff_agenda', 'client_follow_up', 'client_reminder' ) ) ) {
            $cron_reminder = (array) get_option( 'ab_settings_cron_reminder' );
            printf(
                '<div class="form-group">
                    <label for="%1$s">%2$s</label>
                    <p class="help-block">%3$s</p>
                    <select class="form-control ab-auto-w" name="%1$s" id="%1$s">
                        %4$s
                    </select>
                </div>',
                $type . '_cron_hour',
                __( 'Sending time', 'bookly' ),
                __( 'Set the time you want the notification to be sent.', 'bookly' ),
                implode( '', array_map( function ( $hour ) use ( $type, $cron_reminder ) {
                    return sprintf(
                        '<option value="%s" %s>%s</option>',
                        $hour,
                        selected( $cron_reminder[ $type ], $hour, false ),
                        \Bookly\Lib\Utils\DateTime::buildTimeString( $hour * HOUR_IN_SECONDS, false )
                    );
                }, range( 0, 23 ) ) )
            );
        }
    }

}