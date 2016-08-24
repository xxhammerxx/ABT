<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<form method="post">
    <div class="form-inline bookly-margin-bottom-xlg bookly-js-parent-datepicker bookly-relative">
        <div class="form-group">
            <button type="button" class="btn btn-block btn-default" id="reportrange_purchases">
                <i class="dashicons dashicons-calendar-alt"></i>
                <input type="hidden" name="form-purchases">
                <span data-date="<?php echo date( 'Y-m-d', strtotime( '-30 days' ) ) ?> - <?php echo date( 'Y-m-d' ) ?>">
                    <?php echo date_i18n( get_option( 'date_format' ), strtotime( '-30 days' ) ) ?> - <?php echo date_i18n( get_option( 'date_format' ) ) ?>
                </span>
            </button>
        </div>
        <div class="form-group">
            <button type="button" class="btn btn-default" id="get_list_purchases"><?php _e( 'Filter', 'bookly' ) ?></button>
        </div>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
        <tr>
            <th><?php _e( 'Date', 'bookly' ) ?></th>
            <th><?php _e( 'Time', 'bookly' ) ?></th>
            <th><?php _e( 'Type', 'bookly' ) ?></th>
            <th><?php _e( 'Order', 'bookly' ) ?></th>
            <th><?php _e( 'Status', 'bookly' ) ?></th>
            <th><?php _e( 'Amount', 'bookly' ) ?></th>
        </tr>
        </thead>
        <tbody id="pay_orders">
            <tr><td colspan="6"><div class="bookly-loading"></div></td></tr>
        </tbody>
    </table>
</div>