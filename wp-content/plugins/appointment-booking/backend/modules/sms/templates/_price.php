<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="intl-tel-input table-responsive">
    <table class="table table-striped">
        <thead>
        <tr>
            <th style="width: 1%">&nbsp;</th>
            <th><?php _e( 'Country', 'bookly' ) ?></th>
            <th style="width: 20%" class="text-right"><?php _e( 'Code', 'bookly' ) ?></th>
            <th style="width: 20%" class="text-right"><?php _e( 'Price', 'bookly' ) ?></th>
        </tr>
        </thead>
        <tbody id="pricelist">
        <?php if ( $prices ) : ?>
            <?php foreach ( $prices as $price ) : ?>
                <tr>
                    <td><div class="iti-flag <?php echo esc_attr( $price->country_iso_code ) ?>"></div></td>
                    <td><?php echo $price->country_name ?></td>
                    <td class="text-right"><?php echo $price->phone_code ?></td>
                    <td class="text-right">$<?php echo rtrim( $price->price, '0' ) ?></td>
                </tr>
            <?php endforeach ?>
        <?php else : ?>
            <tr><td colspan="4"><div class="bookly-loading"></div></td></tr>
        <?php endif ?>
        </tbody>
    </table>
</div>
<p><?php _e( 'If you do not see your country in the list please contact us at <a href="mailto:support@ladela.com">support@ladela.com</a>.', 'bookly' ) ?></p>