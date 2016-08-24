<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div id="bookly-tbs" class="wrap">
    <div class="bookly-tbs-body">
        <div class="page-header text-right clearfix">
            <div class="bookly-page-title">
                <?php _e( 'Appearance', 'bookly' ) ?>
            </div>
        </div>
        <div class="panel panel-default bookly-main">
            <div class="panel-body">
                <div id="ab-appearance">
                    <div class="bookly-color-picker-wrapper bookly-margin-bottom-lg">
                        <input  type="text" name="color" class="bookly-js-color-picker"
                                value="<?php echo esc_attr( get_option( 'ab_appearance_color' ) ) ?>"
                                data-selected="<?php echo esc_attr( get_option( 'ab_appearance_color' ) ) ?>">
                    </div>

                    <form method="post" id="common_settings">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" id=ab-progress-tracker-checkbox name=ab-progress-tracker-checkbox <?php checked( get_option( 'ab_appearance_show_progress_tracker' ) ) ?>>
                                        <?php _e( 'Show form progress tracker', 'bookly' ) ?>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" id="ab-show-calendar-checkbox" name="ab-show-calendar-checkbox" <?php checked( get_option( 'ab_appearance_show_calendar' ) ) ?>>
                                        <?php _e( 'Show calendar', 'bookly' ) ?>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" id="ab-blocked-timeslots-checkbox" name="ab-blocked-timeslots-checkbox" <?php checked( get_option( 'ab_appearance_show_blocked_timeslots' ) ) ?>>
                                        <?php _e( 'Show blocked timeslots', 'bookly' ) ?>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" id="ab-day-one-column-checkbox" name="ab-day-one-column-checkbox" <?php checked( get_option( 'ab_appearance_show_day_one_column' ) ) ?>>
                                        <?php _e( 'Show each day in one column', 'bookly' ) ?>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" id=ab-required-employee-checkbox name=ab-required-employee-checkbox <?php checked( get_option( 'ab_appearance_required_employee' ) ) ?>>
                                        <?php _e( 'Make selecting employee required', 'bookly' ) ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>

                    <ul class="bookly-nav bookly-nav-tabs bookly-margin-top-xlg" role="tablist">
                        <?php $i = 1; ?>
                        <?php foreach ( $steps as $step => $step_name ) : ?>
                            <?php if ( $step != 2 || \Bookly\Lib\Config::extrasEnabled() ) : ?>
                                <li class="bookly-nav-item <?php if ( $step == 1 ) : ?>active<?php endif ?>" data-target="#ab-step-<?php echo $step ?>" data-toggle="tab">
                                    <?php echo $i++ ?>. <?php echo esc_html( $step_name ) ?>
                                </li>
                            <?php endif ?>
                        <?php endforeach ?>
                    </ul>

                    <div class="bookly-filter">
                        <?php _e( 'Click on the underlined text to edit.', 'bookly' ) ?>
                    </div>

                    <div class="panel panel-default bookly-margin-top-xlg">
                        <div class="panel-body">
                            <div class="tab-content">
                                <?php foreach ( $steps as $step => $step_name ) : ?>
                                    <div id="ab-step-<?php echo $step ?>" class="tab-pane <?php if ( $step == 1 ) : ?>active<?php endif ?>" data-target="<?php echo $step ?>">
                                        <?php // Render unique data per step
                                        switch ( $step ) :
                                            case 1: include '_1_service.php'; break;
                                            case 2: do_action( 'bookly_extras_render_appearance_tab', $this->render( '_progress_tracker', array( 'step' => $step ), false ) );
                                                break;
                                            case 3: include '_3_time.php';    break;
                                            case 4: include '_4_cart.php';    break;
                                            case 5: include '_5_details.php'; break;
                                            case 6: include '_6_payment.php'; break;
                                            case 7: include '_7_done.php';    break;
                                        endswitch ?>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="panel-footer">
                <?php \Bookly\Lib\Utils\Common::submitButton( 'ajax-send-appearance' ) ?>
                <?php \Bookly\Lib\Utils\Common::resetButton() ?>
            </div>
        </div>
    </div>
</div>