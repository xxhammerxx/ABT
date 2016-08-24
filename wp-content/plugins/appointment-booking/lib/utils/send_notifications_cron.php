<?php
namespace Bookly\Lib\Utils;

use Bookly\Lib;
use Bookly\Lib\Entities;

define( 'WP_USE_THEMES', false );
if ( isset( $argv ) ) {
    foreach ( $argv as $argument ) {
        if ( strpos( $argument, 'host=' ) === 0 ) {
            $_SERVER['HTTP_HOST'] = substr( $argument, 5 );
        }
    }
}
require_once __DIR__ . '/../../../../../wp-load.php';
require_once ABSPATH . WPINC . '/formatting.php';
require_once ABSPATH . WPINC . '/general-template.php';
require_once ABSPATH . WPINC . '/pluggable.php';
require_once ABSPATH . WPINC . '/link-template.php';

if ( ! class_exists( '\\Bookly\\Lib\\Plugin' ) ) {
    // Bookly on host is inactive.
    if ( is_multisite() ) {
        $working_directory = getcwd();
        // absolute path for dir appointment-booking
        chdir( realpath( __DIR__ . '/../../' ) );
        include_once 'autoload.php';
        // Restore working directory.
        chdir( $working_directory );
    } else {
        die( 'Bookly is inactive' );
    }
} else {
    add_action( 'bookly_send_notifications', function() { new Notifier(); } );
}

/**
 * Class Notifier
 * @package Bookly\Lib\Utils
 */
class Notifier
{
    private $mysql_now; // format: YYYY-MM-DD HH:MM:SS

    /**
     * @var Lib\SMS
     */
    private $sms;

    private $sms_authorized = false;

    /**
     * @param Entities\Notification $notification
     */
    public function processNotification( Entities\Notification $notification )
    {
        /** @var \wpdb $wpdb */
        global $wpdb;
        $date  = new \DateTime();
        $hours = get_option( 'ab_settings_cron_reminder' );
        $compounds = array();

        switch ( $notification->get( 'type' ) ) {
            case 'staff_agenda':
                if ( $date->format( 'H' ) >= $hours[ $notification->get( 'type' ) ] ) {
                    $rows = $wpdb->get_results(
                        'SELECT
                            `a`.*,
                            `ca`.`locale`,
                            `c`.`name`       AS `customer_name`,
                            `s`.`title`      AS `service_title`,
                            `s`.`info`       AS `service_info`,
                            `st`.`email`     AS `staff_email`,
                            `st`.`phone`     AS `staff_phone`,
                            `st`.`full_name` AS `staff_name`,
                            `st`.`info`      AS `staff_info`
                        FROM `' . Entities\CustomerAppointment::getTableName() . '` `ca`
                        LEFT JOIN `' . Entities\Appointment::getTableName() . '` `a`   ON `a`.`id` = `ca`.`appointment_id`
                        LEFT JOIN `' . Entities\Customer::getTableName() . '` `c`      ON `c`.`id` = `ca`.`customer_id`
                        LEFT JOIN `' . Entities\Service::getTableName() . '` `s`       ON `s`.`id` = `a`.`service_id`
                        LEFT JOIN `' . Entities\Staff::getTableName() . '` `st`        ON `st`.`id` = `a`.`staff_id`
                        LEFT JOIN `' . Entities\StaffService::getTableName() . '` `ss` ON `ss`.`staff_id` = `a`.`staff_id` AND `ss`.`service_id` = `a`.`service_id`
                        WHERE `ca`.`status` != "' . Entities\CustomerAppointment::STATUS_CANCELLED . '" AND
                        DATE(DATE_ADD("' . $this->mysql_now . '", INTERVAL 1 DAY)) = DATE(`a`.`start_date`) AND NOT EXISTS (
                            SELECT * FROM `' . Entities\SentNotification::getTableName() . '` `sn` WHERE
                                DATE(`sn`.`created`) = DATE("' . $this->mysql_now . '") AND
                                `sn`.`gateway`       = "' . $notification->get( 'gateway' ) . '" AND
                                `sn`.`type`          = "staff_agenda" AND
                                `sn`.`staff_id`      = `a`.`staff_id`
                        )
                        ORDER BY `a`.`start_date`'
                    );

                    if ( $rows ) {
                        $appointments = array();
                        foreach ( $rows as $row ) {
                            $appointments[ $row->staff_id ][] = $row;
                        }

                        foreach ( $appointments as $staff_id => $collection ) {
                            $sent = false;
                            $staff_email = null;
                            $staff_phone = null;
                            $is_html = ( get_option( 'ab_email_content_type' ) == 'html' && $notification->get( 'gateway' ) != 'sms' );
                            $table   = $is_html ? '<table>%s</table>' : '%s';
                            $tr      = $is_html ? '<tr><td>%s</td><td>%s</td><td>%s</td></tr>' : "%s %s %s\n";
                            $agenda  = '';
                            foreach ( $collection as $appointment ) {
                                $agenda   .= sprintf(
                                    $tr,
                                    Lib\Utils\DateTime::formatTime( $appointment->start_date ) . '-' . Lib\Utils\DateTime::formatTime( $appointment->end_date ),
                                    Common::getTranslatedString( 'service_' . $appointment->service_id, $appointment->service_title, $appointment->locale ),
                                    $appointment->customer_name
                                );
                                $staff_email = $appointment->staff_email;
                                $staff_phone = $appointment->staff_phone;
                            }
                            $agenda = sprintf( $table, $agenda );

                            if ( $staff_email || $staff_phone ) {
                                $replacement = new Lib\NotificationCodes();
                                $replacement->set( 'next_day_agenda',   $agenda );
                                $replacement->set( 'appointment_start', $appointment->start_date );
                                $replacement->set( 'staff_name',   Common::getTranslatedString( 'staff_' . $appointment->staff_id, $appointment->staff_name, $appointment->locale ) );
                                $replacement->set( 'staff_info',   Common::getTranslatedString( 'staff_' . $appointment->staff_id . '_info', $appointment->staff_info, $appointment->locale ) );
                                $replacement->set( 'service_info', Common::getTranslatedString( 'service_' . $appointment->service_id . '_info', $appointment->service_info, $appointment->locale ) );

                                $message_template = Common::getTranslatedString( $notification->get( 'gateway' ) . '_' . $notification->get( 'type' ), $notification->get( 'message' ), $appointment->locale );
                                $message = $replacement->replace( $message_template, $notification->get( 'gateway' ) );
                                if ( $notification->get( 'gateway' ) == 'email' && $staff_email ) {
                                    $subject = $replacement->replace( Common::getTranslatedString( $notification->get( 'gateway' ) . '_' . $notification->get( 'type' ) . '_subject', $notification->get( 'subject' ), $appointment->locale ) );
                                    $message = get_option( 'ab_email_content_type' ) == 'plain' ? $message : wpautop( $message );
                                    // Send email.
                                    $sent = wp_mail( $staff_email, $subject, $message, Common::getEmailHeaders() );
                                } elseif ( $notification->get( 'gateway' ) == 'sms' && $staff_phone ) {
                                    // Send sms.
                                    $sent = $this->sms->sendSms( $staff_phone, $message );
                                }
                            }

                            if ( $sent ) {
                                $this->_notificationSent( $notification->get( 'type' ), $notification->get( 'gateway' ), null, $staff_id );
                            }
                        }
                    }
                }
                break;
            case 'client_follow_up':
                if ( $date->format( 'H' ) >= $hours[ $notification->get( 'type' ) ] ) {
                    $rows = $wpdb->get_results(
                        'SELECT
                            `ca`.`id`, `ca`.`compound_token`
                        FROM `' . Entities\CustomerAppointment::getTableName() . '` `ca`
                        LEFT JOIN `' . Entities\Appointment::getTableName() . '` `a` ON `a`.`id` = `ca`.`appointment_id`
                        WHERE `ca`.`status` != "' . Entities\CustomerAppointment::STATUS_CANCELLED . '" AND
                        DATE("' . $this->mysql_now . '") = DATE(`a`.`start_date`) AND NOT EXISTS (
                            SELECT * FROM `' . Entities\SentNotification::getTableName() . '` `sn` WHERE
                                DATE(`sn`.`created`)           = DATE("' . $this->mysql_now . '") AND
                                `sn`.`gateway`                 = "' . $notification->get( 'gateway' ) . '" AND
                                `sn`.`type`                    = "client_follow_up" AND
                                `sn`.`customer_appointment_id` = `ca`.`id`
                        ) ORDER BY `a`.`start_date`',
                        ARRAY_A
                    );

                    if ( $rows ) {
                        foreach ( $rows as $row ) {
                            if ( ! in_array( $row['compound_token'], $compounds ) ) {
                                $customer_appointment = new Entities\CustomerAppointment();
                                $customer_appointment->load( $row['id'] );
                                if ( Lib\NotificationSender::sendFromCron( $notification, $customer_appointment ) ) {
                                    $this->_notificationSent( $notification->get( 'type' ), $notification->get( 'gateway' ), $row['id'] );
                                }
                                if ( ! empty( $row['compound_token'] ) ) {
                                    $compounds[] = $row['compound_token'];
                                }
                            } else {
                                // Mark the 'sent' notifications for services included in the compound service.
                                $this->_notificationSent( $notification->get( 'type' ), $notification->get( 'gateway' ), $row['id'] );
                            }
                        }
                    }
                }
                break;
            case 'client_reminder':
                if ( $date->format( 'H' ) >= $hours[ $notification->get( 'type' ) ] ) {
                    $rows = $wpdb->get_results(
                        'SELECT
                            `ca`.`id`, `ca`.`compound_token`
                        FROM `' . Entities\CustomerAppointment::getTableName() . '` `ca`
                        LEFT JOIN `' . Entities\Appointment::getTableName() . '` `a` ON `a`.`id` = `ca`.`appointment_id`
                        WHERE `ca`.`status` != "' . Entities\CustomerAppointment::STATUS_CANCELLED . '" AND
                        DATE(DATE_ADD("' . $this->mysql_now . '", INTERVAL 1 DAY)) = DATE(`a`.`start_date`) AND NOT EXISTS (
                            SELECT * FROM `' . Entities\SentNotification::getTableName() . '` `sn` WHERE
                                DATE(`sn`.`created`)           = DATE("' . $this->mysql_now . '") AND
                                `sn`.`gateway`                 = "' . $notification->get( 'gateway' ) . '" AND
                                `sn`.`type`                    = "client_reminder" AND
                                `sn`.`customer_appointment_id` = `ca`.`id`
                        ) ORDER BY `a`.`start_date`',
                        ARRAY_A
                    );

                    if ( $rows ) {
                        foreach ( $rows as $row ) {
                            if ( ! in_array( $row['compound_token'], $compounds ) ) {
                                $customer_appointment = new Entities\CustomerAppointment();
                                $customer_appointment->load( $row['id'] );
                                if ( Lib\NotificationSender::sendFromCron( $notification, $customer_appointment ) ) {
                                    $this->_notificationSent( $notification->get( 'type' ), $notification->get( 'gateway' ), $row['id'] );
                                }
                                if ( ! empty( $row['compound_token'] ) ) {
                                    $compounds[] = $row['compound_token'];
                                }
                            } else {
                                // Mark the 'sent' notifications for services included in the compound service.
                                $this->_notificationSent( $notification->get( 'type' ), $notification->get( 'gateway' ), $row['id'] );
                            }
                        }
                    }
                }
                break;
        }
    }

    /**
     * Mark sent notification.
     *
     * @param      $type
     * @param      $gateway
     * @param      $ca_id
     * @param null $staff_id
     */
    private function _notificationSent( $type, $gateway, $ca_id, $staff_id = null )
    {
        $sent_notification = new Entities\SentNotification();
        $sent_notification->set( 'customer_appointment_id', $ca_id )
            ->set( 'type',     $type )
            ->set( 'staff_id', $staff_id )
            ->set( 'gateway',  $gateway )
            ->set( 'created',  $this->mysql_now )
            ->save();
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        date_default_timezone_set( Common::getTimezoneString() );

        wp_load_translations_early();

        $now = new \DateTime();
        $this->mysql_now = $now->format( 'Y-m-d H:i:s' );
        $this->sms = new Lib\SMS();
        $this->sms_authorized = $this->sms->loadProfile();

        $query = Entities\Notification::query()
            ->where( 'active', 1 )
            ->whereIn( 'type', array( 'staff_agenda', 'client_follow_up', 'client_reminder' ) );

        foreach ( $query->find() as $notification ) {
            $this->processNotification( $notification );
        }
    }

}

do_action( 'bookly_send_notifications' );