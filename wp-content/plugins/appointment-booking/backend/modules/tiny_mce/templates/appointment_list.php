<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div id="ab-tinymce-appointment-popup" style="display: none">
    <form id="ab-shortcode-form">
        <table>
            <tr>
                <td class="ab-title-col"><?php _e( 'Columns', 'bookly' ) ?></td>
                <td>
                    <label><input type="checkbox" data-column="category" /><?php _e( 'Category', 'bookly' ) ?></label>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <label><input type="checkbox" data-column="service" /><?php _e( 'Service', 'bookly' ) ?></label>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <label><input type="checkbox" data-column="staff" /><?php _e( 'Staff', 'bookly' ) ?></label>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <label><input type="checkbox" data-column="date" /><?php _e( 'Date', 'bookly' ) ?></label>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <label><input type="checkbox" data-column="time" /><?php _e( 'Time', 'bookly' ) ?></label>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <label><input type="checkbox" data-column="price" /><?php _e( 'Price', 'bookly' ) ?></label>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <label><input type="checkbox" data-column="status" /><?php _e( 'Status', 'bookly' ) ?></label>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <label><input type="checkbox" data-column="cancel" /><?php _e( 'Cancel', 'bookly' ) ?></label>
                </td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td></td>
            </tr>
            <tr>
                <td class="ab-title-col"><?php _e( 'Titles', 'bookly' ) ?></td>
                <td>
                    <label><input type="checkbox" id="ab-show-column-titles" /><?php _e( 'Yes', 'bookly' ) ?></label>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <input class="button button-primary" id="ab-insert-ap-shortcode" type="submit" value="<?php esc_attr_e( 'Insert', 'bookly' ) ?>" />
                </td>
            </tr>
        </table>
    </form>
</div>

<style type="text/css">
    #ab-shortcode-form table td { padding: 5px; vertical-align: 0; }
    #ab-shortcode-form table td.ab-title-col { width: 80px; }
</style>

<script type="text/javascript">
    jQuery(function ($) {
        var $add_button_appointment = $('#add-ap-appointment');
        var $insert                 = $('#ab-insert-ap-shortcode');

        $add_button_appointment.on('click', function () {
            window.parent.tb_show(<?php echo json_encode( __( 'Add Bookly appointments list', 'bookly' ) ) ?>, this.href);
            window.setTimeout(function(){
                $('#TB_window').css({
                    'overflow-x': 'auto',
                    'overflow-y': 'hidden'
                });
            },100);
        });

        $insert.on('click', function (e) {
            e.preventDefault();

            var shortcode = '[bookly-appointments-list';

            // columns
            var columns = $('[data-column]:checked');
            if (columns.length) {
                var col = [];
                $.each(columns, function() {
                    col.push($(this).data('column'));
                });
                shortcode += ' columns="' + col.join(',') + '"';
            }

            if ($('#ab-show-column-titles:checked').length) {
                shortcode += ' show_column_titles="1"';
            }

            window.send_to_editor(shortcode + ']');
            window.parent.tb_remove();
            return false;
        });
    });
</script>