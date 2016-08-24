<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<form method="post" action="<?php echo esc_url(add_query_arg('tab', 'company')) ?>" enctype="multipart/form-data"
      class="ab-settings-form">
    <div class="row">
        <div class="col-xs-3 col-lg-2">
            <div class="bookly-flexbox">
                <div id="bookly-js-logo" class="bookly-thumb bookly-thumb-lg bookly-margin-right-lg">
                    <input type="hidden" name="ab_settings_company_logo_attachment_id" data-default="<?php echo esc_attr( get_option( 'ab_settings_company_logo_attachment_id' ) ) ?>"
                           value="<?php echo esc_attr( get_option( 'ab_settings_company_logo_attachment_id' ) ) ?>">
                    <div class="bookly-flex-cell">
                        <div class="form-group">
                            <?php $img = wp_get_attachment_image_src( get_option( 'ab_settings_company_logo_attachment_id' ), 'thumbnail' ) ?>
                            <div class="bookly-js-image bookly-thumb bookly-thumb-lg bookly-margin-right-lg"
                                 data-style="<?php echo $img ? 'background-image: url(' . $img[0] . '); background-size: cover;' : '' ?>"
                                <?php echo $img ? 'style="background-image: url(' . $img[0] . '); background-size: cover;"' : '' ?>
                            >
                                <a class="dashicons dashicons-trash text-danger bookly-thumb-delete"
                                   href="javascript:void(0)"
                                   title="<?php _e( 'Delete', 'bookly' ) ?>"
                                   <?php if ( ! $img ) : ?>style="display: none;"<?php endif ?>>
                                </a>
                                <div class="bookly-thumb-edit">
                                    <div class="bookly-pretty">
                                        <label class="bookly-pretty-indicator bookly-thumb-edit-btn">
                                            <?php _e( 'Image', 'bookly' ) ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xs-9 col-lg-10">
            <div class="bookly-flex-cell bookly-vertical-middle">
                <label for="ab_settings_company_name"><?php _e( 'Company name', 'bookly' ) ?></label>
                <input class="form-control" id="ab_settings_company_name" name="ab_settings_company_name"
                       value="<?php echo esc_attr( get_option( 'ab_settings_company_name' ) ) ?>" type="text">
            </div>
        </div>
    </div>

    <div class="form-group">
        <label for="ab_settings_company_address"><?php _e( 'Address', 'bookly' ) ?></label>
        <textarea id="ab_settings_company_address" class="form-control" rows="5"
                  name="ab_settings_company_address"><?php echo esc_attr( get_option( 'ab_settings_company_address' ) ) ?></textarea>
    </div>
    <div class="form-group">
        <label for="ab_settings_company_phone"><?php _e( 'Phone', 'bookly' ) ?></label>
        <input id="ab_settings_company_phone" class="form-control" type="text" name="ab_settings_company_phone"
               value="<?php echo esc_attr( get_option( 'ab_settings_company_phone' ) ) ?>"/>
    </div>
    <div class="form-group">
        <label for="ab_settings_company_website"><?php _e( 'Website', 'bookly' ) ?></label>
        <input id="ab_settings_company_website" class="form-control" type="text" name="ab_settings_company_website"
               value="<?php echo esc_attr( get_option( 'ab_settings_company_website' ) ) ?>"/>
    </div>

    <div class="panel-footer">
        <?php \Bookly\Lib\Utils\Common::submitButton() ?>
        <?php \Bookly\Lib\Utils\Common::resetButton( 'ab-settings-company-reset' ) ?>
    </div>
</form>