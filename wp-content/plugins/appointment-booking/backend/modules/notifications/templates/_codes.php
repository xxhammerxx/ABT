<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<tr><td><input value="[[APPOINTMENT_DATE]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'date of appointment', 'bookly' ) ?></td></tr>
<tr><td><input value="[[APPOINTMENT_TIME]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'time of appointment', 'bookly' ) ?></td></tr>
<tr><td><input value="[[BOOKING_NUMBER]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'booking number', 'bookly' ) ?></td></tr>
<tr><td><input value="[[APPROVE_APPOINTMENT_URL]]" readonly="readonly" onclick="this.select()" /> - <?php esc_html_e( 'URL of approve appointment link (to use inside <a> tag)', 'bookly' ) ?></td></tr>
<tr><td><input value="[[CANCEL_APPOINTMENT]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'cancel appointment link', 'bookly' ) ?></td></tr>
<tr><td><input value="[[CANCEL_APPOINTMENT_URL]]" readonly="readonly" onclick="this.select()" /> - <?php esc_html_e( 'URL of cancel appointment link (to use inside <a> tag)', 'bookly' ) ?></td></tr>
<tr><td><input value="[[CATEGORY_NAME]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'name of category', 'bookly' ) ?></td></tr>
<tr><td><input value="[[CLIENT_EMAIL]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'email of client', 'bookly' ) ?></td></tr>
<tr><td><input value="[[CLIENT_NAME]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'name of client', 'bookly' ) ?></td></tr>
<tr><td><input value="[[CLIENT_PHONE]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'phone of client', 'bookly' ) ?></td></tr>
<tr><td><input value="[[COMPANY_NAME]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'name of company', 'bookly' ) ?></td></tr>
<tr><td><input value="[[COMPANY_LOGO]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'company logo', 'bookly' ) ?></td></tr>
<tr><td><input value="[[COMPANY_ADDRESS]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'address of company', 'bookly' ) ?></td></tr>
<tr><td><input value="[[COMPANY_PHONE]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'company phone', 'bookly' ) ?></td></tr>
<tr><td><input value="[[COMPANY_WEBSITE]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'company web-site address', 'bookly' ) ?></td></tr>
<tr><td><input value="[[CUSTOM_FIELDS]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'combined values of all custom fields', 'bookly' ) ?></td></tr>
<tr><td><input value="[[CUSTOM_FIELDS_2C]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'combined values of all custom fields (formatted in 2 columns)', 'bookly' ) ?></td></tr>
<?php do_action( 'bookly_render_notification_codes_after_c' ) ?>
<tr><td><input value="[[GOOGLE_CALENDAR_URL]]" readonly="readonly" onclick="this.select()" /> - <?php esc_html_e( 'URL for adding event to client\'s Google Calendar (to use inside <a> tag)', 'bookly' ) ?></td></tr>
<tr><td><input value="[[NUMBER_OF_PERSONS]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'number of persons', 'bookly' ) ?></td></tr>
<tr><td><input value="[[SERVICE_INFO]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'info of service', 'bookly' ) ?></td></tr>
<tr><td><input value="[[SERVICE_NAME]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'name of service', 'bookly' ) ?></td></tr>
<tr><td><input value="[[SERVICE_PRICE]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'price of service', 'bookly' ) ?></td></tr>
<tr><td><input value="[[STAFF_EMAIL]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'email of staff', 'bookly' ) ?></td></tr>
<tr><td><input value="[[STAFF_INFO]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'info of staff', 'bookly' ) ?></td></tr>
<tr><td><input value="[[STAFF_NAME]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'name of staff', 'bookly' ) ?></td></tr>
<tr><td><input value="[[STAFF_PHONE]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'phone of staff', 'bookly' ) ?></td></tr>
<tr><td><input value="[[STAFF_PHOTO]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'photo of staff', 'bookly' ) ?></td></tr>
<tr><td><input value="[[TOTAL_PRICE]]" readonly="readonly" onclick="this.select()" /> - <?php _e( 'total price of booking (sum of all cart items after applying coupon)', 'bookly' ) ?></td></tr>