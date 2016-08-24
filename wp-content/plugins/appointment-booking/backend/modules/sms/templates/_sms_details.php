<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<form method="post">
    <div class="form-inline bookly-margin-bottom-xlg bookly-js-parent-datepicker bookly-relative">
        <div class="form-group">
            <button type="button" class="btn btn-block btn-default" id="reportrange_sms">
                <i class="dashicons dashicons-calendar-alt"></i>
                <input type="hidden" name="form-purchases">
                <span data-date="<?php echo date( 'Y-m-d', strtotime( '-30 days' ) ) ?> - <?php echo date( 'Y-m-d' ) ?>">
                    <?php echo date_i18n( get_option( 'date_format' ), strtotime( '-30 days' ) ) ?> - <?php echo date_i18n( get_option( 'date_format' ) ) ?>
                </span>
            </button>
        </div>
        <div class="form-group">
            <button type="button" class="btn btn-default" id="get_list_sms"><?php _e( 'Filter', 'bookly' ) ?></button>
        </div>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
        <tr>
            <th style="width: 15%"><?php _e( 'Date', 'bookly' ) ?></th>
            <th style="width: 10%"><?php _e( 'Time', 'bookly' ) ?></th>
            <th style="width: 25%"><?php _e( 'Text', 'bookly' ) ?></th>
            <th style="width: 20%"><?php _e( 'Phone', 'bookly' ) ?></th>
            <th style="width: 10%" class="text-right"><?php _e( 'Cost', 'bookly' ) ?></th>
            <th style="width: 20%"><?php _e( 'Status', 'bookly' ) ?></th>
        </tr>
        </thead>
        <tbody id="list_sms">
        <tr><td colspan="6"><div class="bookly-loading"></div></td></tr>
        </tbody>
    </table>
</div>
