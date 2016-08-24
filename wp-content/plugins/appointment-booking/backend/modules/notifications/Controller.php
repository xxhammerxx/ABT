<?php
namespace Bookly\Backend\Modules\Notifications;

use \Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Backend\Modules\Notifications
 */
class Controller extends Lib\Base\Controller
{
    public function index()
    {
        $this->enqueueStyles( array(
            'frontend' => array( 'css/ladda.min.css' ),
            'backend'  => array( 'bootstrap/css/bootstrap-theme.min.css', ),
        ) );

        $this->enqueueScripts( array(
            'backend'  => array(
                'bootstrap/js/bootstrap.min.js' => array( 'jquery' ),
                'js/angular.min.js',
                'js/help.js'  => array( 'jquery' ),
                'js/alert.js' => array( 'jquery' ),
            ),
            'module'   => array(
                'js/notification.js' => array( 'jquery' ),
                'js/ng-app.js' => array( 'jquery', 'ab-angular.min.js' ),
            ),
            'frontend' => array(
                'js/spin.min.js'  => array( 'jquery' ),
                'js/ladda.min.js' => array( 'jquery' ),
            )
        ) );
        $cron_reminder = (array) get_option( 'ab_settings_cron_reminder' );
        $form  = new Forms\Notifications( 'email' );
        $alert = array( 'success' => array() );
        // Save action.
        if ( ! empty ( $_POST ) ) {
            $form->bind( $this->getPostParameters(), $_FILES );
            $form->save();
            $alert['success'][] = __( 'Settings saved.', 'bookly' );
            // sender name
            if ( $this->hasParameter( 'ab_settings_sender_name' ) ) {
                update_option( 'ab_settings_sender_name', $this->getParameter( 'ab_settings_sender_name' ) );
            }
            // sender email
            if ( $this->hasParameter( 'ab_settings_sender_email' ) ) {
                update_option( 'ab_settings_sender_email', $this->getParameter( 'ab_settings_sender_email' ) );
            }
            if ( $this->hasParameter( 'ab_email_notification_reply_to_customers' ) ) {
                update_option( 'ab_email_notification_reply_to_customers', $this->getParameter( 'ab_email_notification_reply_to_customers' ) );
            }
            if ( $this->hasParameter( 'ab_email_content_type' ) ) {
                update_option( 'ab_email_content_type', $this->getParameter( 'ab_email_content_type' ) );
            }
            foreach ( array( 'staff_agenda', 'client_follow_up', 'client_reminder' ) as $type ) {
                $cron_reminder[ $type ] = $this->getParameter( $type . '_cron_hour' );
            }
            update_option( 'ab_settings_cron_reminder', $cron_reminder );
        }
        $cron_path = realpath( Lib\Plugin::getDirectory() . '/lib/utils/send_notifications_cron.php' );
        wp_localize_script( 'ab-alert.js', 'BooklyL10n',  array(
            'alert' => $alert,
            'sent_successfully' => 'Sent successfully'
        ) );
        $this->render( 'index', compact( 'form', 'cron_path', 'cron_reminder' ) );
    }

    public function executeGetEmailNotificationsData()
    {
        $result = array();

        $form = new Forms\Notifications( 'email' );

        $ab_settings_sender_name  = get_option( 'ab_settings_sender_name' ) == '' ?
            get_option( 'blogname' )    : get_option( 'ab_settings_sender_name' );

        $ab_settings_sender_email = get_option( 'ab_settings_sender_email' ) == '' ?
            get_option( 'admin_email' ) : get_option( 'ab_settings_sender_email' );

        $result[ 'ab_notifications' ] = $form->getData();
        $result[ 'ab_settings_sender_name' ] = $ab_settings_sender_name;
        $result[ 'ab_settings_sender_email' ] = $ab_settings_sender_email;
        $result[ 'ab_types' ] = $form->types;

        wp_send_json_success( $result );
    }

    public function executeTestEmailNotifications()
    {
        $to_email   = $this->getParameter( 'to_email' );
        $sender = array(
            'name'  => $this->getParameter( 'ab_settings_sender_name' ),
            'email' => $this->getParameter( 'ab_settings_sender_email' ),
        );
        $reply_to_customers = $this->getParameter( 'reply_to_customers' );
        $content_type       = $this->getParameter( 'content_type' );

        $notifications = array();
        foreach ( $this->getParameter( 'notifications' ) as $notification ) {
            if ( $notification['active'] == '1' ) {
                $notifications[] = $notification['type'];
            }
        }

        Lib\NotificationSender::sendTestEmailNotifications( $to_email, $sender, $reply_to_customers, $content_type, $notifications );

        wp_send_json_success( $_POST );
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