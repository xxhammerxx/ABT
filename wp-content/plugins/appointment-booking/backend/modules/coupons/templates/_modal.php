<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="modal fade" id="bookly-coupon-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form>
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="bookly-new-coupon-title"><?php _e( 'New coupon', 'bookly' ) ?></h4>
                    <h4 class="modal-title" id="bookly-edit-coupon-title"><?php _e( 'Edit coupon', 'bookly' ) ?></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class=form-group>
                                <label for="bookly-coupon-code"><?php _e( 'Code', 'bookly' ) ?></label>
                                <input type="text" id="bookly-coupon-code" class="form-control" />
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class=form-group>
                                <label for="bookly-coupon-discount"><?php _e( 'Discount (%)', 'bookly' ) ?></label>
                                <input type="number" id="bookly-coupon-discount" class="form-control" />
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class=form-group>
                                <label for="bookly-coupon-deduction"><?php _e( 'Deduction', 'bookly' ) ?></label>
                                <input type="text" id="bookly-coupon-deduction" class="form-control" />
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class=form-group>
                                <label for="bookly-coupon-usage-limit"><?php _e( 'Usage limit', 'bookly' ) ?></label>
                                <input type="number" id="bookly-coupon-usage-limit" class="form-control" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php \Bookly\Lib\Utils\Common::submitButton( 'bookly-coupon-save' ) ?>
                    <button class="btn btn-lg btn-default" data-dismiss="modal">
                        <?php _e( 'Cancel', 'bookly' ) ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>