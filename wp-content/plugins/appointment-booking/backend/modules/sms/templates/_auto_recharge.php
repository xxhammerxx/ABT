<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<p class="alert alert-info">
    <?php _e( 'We will only charge your PayPal account when your balance falls below $10.', 'bookly' ) ?>
</p>

<div id="ab-preapproval-form-init">
    <div class="form-group">
        <label for="ab_auto_recharge_amount"><?php _e( 'Amount', 'bookly' ) ?></label>
        <select id="ab_auto_recharge_amount" class="form-control">
            <?php foreach ( array( 10, 25, 50, 100 ) as $amount ) : ?>
                <?php printf( '<option value="%1$s" %2$s>$%1$s</option>', $amount, selected( $recharge_amount == $amount, true, false ) ) ?>
            <?php endforeach ?>
        </select>
    </div>

    <div class="panel-footer">
        <?php \Bookly\Lib\Utils\Common::submitButton( 'ab-preapproval-create', '', __( 'Enable Auto-Recharge', 'bookly') ); ?>
        <button class="btn btn-lg btn-default" disabled><?php _e( 'Disable Auto-Recharge', 'bookly' ) ?></button>
    </div>
</div>

<div id="ab-preapproval-form-decline">
    <div class="form-group">
        <label><?php _e( 'Amount', 'bookly' ) ?></label>
        <input type="text" value="$<?php echo $recharge_amount ?>" disabled class="form-control">
    </div>

    <hr>
    <div class="panel-footer">
        <button data-spinner-size="40" data-style="zoom-in" class="btn btn-success" disabled="disabled"><?php _e( 'Enable Auto-Recharge', 'bookly' ) ?></button>
        <button id="ab-preapproval-decline" data-spinner-size="40" data-style="zoom-in" class="btn btn-default ladda-button"><span class="ladda-label"><?php _e( 'Disable Auto-Recharge', 'bookly' ) ?></span></button>
    </div>
</div>