jQuery(function($) {

    var
        $coupons_list       = $('#bookly-coupons-list'),
        $check_all_button   = $('#bookly-check-all'),
        $coupon_modal       = $('#bookly-coupon-modal'),
        $coupon_new_title   = $('#bookly-new-coupon-title'),
        $coupon_edit_title  = $('#bookly-edit-coupon-title'),
        $coupon_code        = $('#bookly-coupon-code'),
        $coupon_discount    = $('#bookly-coupon-discount'),
        $coupon_deduction   = $('#bookly-coupon-deduction'),
        $coupon_usage_limit = $('#bookly-coupon-usage-limit'),
        $save_button        = $('#bookly-coupon-save'),
        $add_button         = $('#bookly-add'),
        $delete_button      = $('#bookly-delete'),
        row
        ;

    /**
     * Init DataTables.
     */
    var dt = $coupons_list.DataTable({
        order: [[ 0, "asc" ]],
        paging: false,
        info: false,
        searching: false,
        processing: true,
        responsive: true,
        ajax: {
            url: ajaxurl,
            data: { action: 'ab_get_coupons' }
        },
        columns: [
            { data: "code" },
            { data: "discount" },
            { data: "deduction" },
            { data: "usage_limit" },
            { data: "used" },
            {
                responsivePriority: 1,
                orderable: false,
                searchable: false,
                render: function ( data, type, row, meta ) {
                    return '<button type="button" class="btn btn-default" data-toggle="modal" data-target="#bookly-coupon-modal"><i class="glyphicon glyphicon-edit"></i> ' + BooklyL10n.edit + '</button>';
                }
            },
            {
                responsivePriority: 1,
                orderable: false,
                searchable: false,
                render: function ( data, type, row, meta ) {
                    return '<input type="checkbox" value="' + row.id + '">';
                }
            }
        ],
        language: {
            zeroRecords: BooklyL10n.zeroRecords,
            processing:  BooklyL10n.processing
        }
    });

    /**
     * Select all coupons.
     */
    $check_all_button.on('change', function () {
        $coupons_list.find('tbody input:checkbox').prop('checked', this.checked);
    });

    /**
     * On coupon select.
     */
    $coupons_list.on('change', 'tbody input:checkbox', function () {
        $check_all_button.prop('checked', $coupons_list.find('tbody input:not(:checked)').length == 0);
    });

    /**
     * Edit coupon.
     */
    $coupons_list.on('click', 'button', function () {
        row = dt.row($(this).closest('td'));
    });

    /**
     * New coupon.
     */
    $add_button.on('click', function () {
        row = null;
    });

    /**
     * On show modal.
     */
    $coupon_modal.on('show.bs.modal', function () {
        if (row) {
            var coupon = row.data();
            $coupon_code.val(coupon.code);
            $coupon_discount.val(coupon.discount);
            $coupon_deduction.val(coupon.deduction);
            $coupon_usage_limit.val(coupon.usage_limit);
            $coupon_edit_title.show();
            $coupon_new_title.hide();
        } else {
            $coupon_code.val('');
            $coupon_discount.val('0');
            $coupon_deduction.val('0');
            $coupon_usage_limit.val('1');
            $coupon_edit_title.hide();
            $coupon_new_title.show();
        }
    });

    /**
     * Save coupon.
     */
    $save_button.on('click', function (e) {
        e.preventDefault();

        var ladda = Ladda.create(this);
        ladda.start();

        var data         = row ? row.data() : {};
        data.code        = $coupon_code.val();
        data.discount    = $coupon_discount.val();
        data.deduction   = $coupon_deduction.val();
        data.usage_limit = $coupon_usage_limit.val();

        $.ajax({
            url  : ajaxurl,
            type : 'POST',
            data : {
                action : 'ab_save_coupon',
                data   : data
            },
            dataType : 'json',
            success  : function(response) {
                ladda.stop();
                if (response.success) {
                    if (row) {
                        row.data(response.data).draw();
                    } else {
                        dt.row.add(response.data).draw();
                    }
                    $coupon_modal.modal('hide');
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

    /**
     * Delete coupons.
     */
    $delete_button.on('click', function () {
        if (confirm(BooklyL10n.are_you_sure)) {
            var ladda = Ladda.create(this);
            ladda.start();

            var data = [];
            var $checkboxes = $coupons_list.find('input:checked');
            $checkboxes.each(function () {
                data.push(this.value);
            });

            $.ajax({
                url  : ajaxurl,
                type : 'POST',
                data : {
                    action : 'ab_delete_coupons',
                    data   : data
                },
                dataType : 'json',
                success  : function(response) {
                    ladda.stop();
                    if (response.success) {
                        dt.rows($checkboxes.closest('td')).remove().draw();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        }
    });
});
