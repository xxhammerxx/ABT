jQuery(function($) {

    var $form_login    = $('.ab-login-form'),
        $form_forgot   = $('.ab-forgot-form'),
        $form_register = $('.ab-register-form'),
        $list_pusrchases_tbody = $('#pay_orders'),
        $list_sms_tbody = $('#list_sms'),
        $pricelist_tbody = $('#pricelist');

    booklyAlert(BooklyL10n.alert);

    $('.show-register-form').on('click', function (e) {
        e.preventDefault();
        $form_login.hide();
        $form_register.show();
        $form_forgot.hide();
    });

    $('.ab--show-login-form').on('click', function (e) {
        e.preventDefault();
        $form_login.show();
        $form_register.hide();
        $form_forgot.hide();
    });

    $('.show-forgot-form').on('click', function (e) {
        e.preventDefault();
        $form_forgot.show();
        $form_login.hide();
        $form_register.hide();
    });

    var list_price_filter = function(){
        $.ajax({
            type        : 'POST',
            url         : ajaxurl,
            data        : { action: 'ab_get_price_list' },
            dataType    : 'json',
            xhrFields   : { withCredentials: true },
            crossDomain : 'withCredentials' in new XMLHttpRequest(),
            success     : function (response) {
                if (response.success) {
                    $pricelist_tbody.html('');
                    jQuery.each(response.list, function (i, val) {
                        $pricelist_tbody.append('<tr><td><div class="iti-flag ' + val.country_iso_code + '"></div></td><td>' + val.country_name + '</td><td class="text-right">' + val.phone_code + '</td><td class="text-right">$' + val.price.replace(/0+$/, '') + '</td></tr>');
                    });
                } else {
                    if (response.message) {
                        alert(response.message);
                    }
                }
            }
        });
    };

    var list_sms_filter = function () {
        $.ajax({
            type        : 'POST',
            url         : ajaxurl,
            data        : { action: 'ab_get_sms_list', range: $('#reportrange_sms span').data('date') },
            dataType    : 'json',
            xhrFields   : { withCredentials: true },
            crossDomain : 'withCredentials' in new XMLHttpRequest(),
            success     : function (response) {
                if (response.success) {
                    $list_sms_tbody.html('');
                    jQuery.each(response.list, function (i, val) {
                        $list_sms_tbody.append( '<tr><td>' + val.date + '</td><td>' + val.time +'</td><td>' + val.message + '</td><td>' + val.phone +'</td><td class="text-right">' + val.charge + '</td><td>' + val.status + '</td></tr>' );
                    });
                } else {
                    if (response.message) {
                        alert(response.message);
                    }
                }
            }
        });
    };

    var list_purchases_filter = function () {
        $.ajax({
            type        : 'POST',
            url         : ajaxurl,
            data        : { action: 'ab_get_purchases_list', range: $('#reportrange_purchases span').data('date') },
            dataType    : 'json',
            xhrFields   : { withCredentials: true },
            crossDomain : 'withCredentials' in new XMLHttpRequest(),
            success     : function (response) {
                if (response.success) {
                    $list_pusrchases_tbody.html('');
                    jQuery.each( response.list, function( i, val ) {
                        $list_pusrchases_tbody.append( '<tr><td>' + val.date + '</td><td>' + val.time +'</td><td>' + val.type + '</td><td>' + val.order +'</td><td>' + val.status +'</td><td>$' + val.amount + '</td></tr>' );
                    });
                } else {
                    if (response.message) {
                        alert(response.message);
                    }
                }
            }
        });
    };

    $('.form-forgot-next').on('click', function (e) {
        e.preventDefault();
        var $btn  = $(this);
        var $form = $(this).parents('form');
        var $code = $form.find('input[name="code"]');
        var $pwd  = $form.find('input[name="password"]');
        var $username   = $form.find('input[name="username"]');
        var $pwd_repeat = $form.find('input[name="password_repeat"]');
        var data  = { action: 'ab_forgot_password', step: $btn.data('step'), 'username': $username.val() };
        switch ($(this).data('step')) {
            case 0:
                forgot_helper( data, function() {
                    $username.parent().addClass('hidden');
                    $code.parent().removeClass('hidden');
                    $btn.data('step', 1);
                });
                break;
            case 1:
                data.code = $code.val();
                forgot_helper(data, function() {
                    $code.parent().addClass('hidden');
                    $pwd.parent().removeClass('hidden');
                    $pwd_repeat.parent().removeClass('hidden');
                    $btn.data('step', 2);
                });
                break;
            case 2:
                data.code = $code.val();
                data.password = $pwd.val();
                data.password_repeat = $pwd_repeat.val();
                if (data.password == data.password_repeat && data.password != '') {
                    forgot_helper(data, function() {
                        $('.ab--show-login-form').trigger('click');
                        $btn.data('step', 0);
                        $username.parent().removeClass('hidden');
                        $pwd.parent().addClass('hidden');
                        $pwd_repeat.parent().addClass('hidden');
                        $form.trigger('reset');
                    });
                } else {
                    alert(BooklyL10n.passwords_no_same);
                }
                break;
        }
    });

    function forgot_helper(data, callback) {
        $.ajax({
            method     : 'POST',
            url        : ajaxurl,
            data       : data,
            dataType   : 'json',
            xhrFields  : {withCredentials: true},
            crossDomain: 'withCredentials' in new XMLHttpRequest(),
            success    : function (response) {
                if (response.success) {
                    callback();
                } else {
                    if(response.data && response.data.message) { alert(response.data.message); }
                }
            }
        });
    }

    $('#get_list_purchases').on('click', list_purchases_filter);
    $('#get_list_sms').on('click', list_sms_filter);
    $("[data-target='#sms_details']").on('click', function(){
            list_sms_filter();
            $(this).unbind( 'click' );
        }
    );
    $("[data-target='#purchases']").on('click', function(){
            list_purchases_filter();
            $(this).unbind( 'click' );
        }
    );
    $("[data-target='#price']").on('click', function(){
            list_price_filter();
            $(this).unbind( 'click' );
        }
    );

    var $range_purchases = $('#reportrange_purchases span'),
        $range_sms = $('#reportrange_sms span'),
        picker_ranges = {};

    picker_ranges[BooklyL10n.yesterday]  = [moment().subtract(1, 'days'), moment().subtract(1, 'days')];
    picker_ranges[BooklyL10n.today]      = [moment(), moment()];
    picker_ranges[BooklyL10n.last_7]     = [moment().subtract(7, 'days'), moment()];
    picker_ranges[BooklyL10n.last_30]    = [moment().subtract(30, 'days'), moment()];
    picker_ranges[BooklyL10n.this_month] = [moment().startOf('month'), moment().endOf('month')];
    picker_ranges[BooklyL10n.last_month] = [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')];
    moment.locale('en', {
        months       : BooklyL10n.calendar.longMonths,
        monthsShort  : BooklyL10n.calendar.shortMonths,
        weekdays     : BooklyL10n.calendar.longDays,
        weekdaysShort: BooklyL10n.calendar.shortDays,
        weekdaysMin  : BooklyL10n.calendar.shortDays
    });
    var locale = {
        applyLabel      : BooklyL10n.apply,
        cancelLabel     : BooklyL10n.cancel,
        fromLabel       : BooklyL10n.from,
        toLabel         : BooklyL10n.to,
        customRangeLabel: BooklyL10n.custom_range,
        daysOfWeek      : BooklyL10n.calendar.shortDays,
        monthNames      : BooklyL10n.calendar.longMonths,
        firstDay        : parseInt(BooklyL10n.startOfWeek),
        format          : BooklyL10n.mjsDateFormat
    };

    $('#reportrange_purchases').daterangepicker(
        {
            parentEl : $('#reportrange_purchases').closest('.bookly-js-parent-datepicker'),
            startDate: moment().subtract(30, 'days'), // by default selected is "Last 30 days"
            ranges   : picker_ranges,
            locale   : locale
        },
        function (start, end) {
            var format = 'YYYY-MM-DD';
            $range_purchases
                .data('date', start.format(format) + ' - ' + end.format(format))
                .html(start.format(BooklyL10n.mjsDateFormat) + ' - ' + end.format(BooklyL10n.mjsDateFormat));
        }
    );

    $('#reportrange_sms').daterangepicker(
        {
            parentEl : $('#reportrange_sms').closest('.bookly-js-parent-datepicker'),
            startDate: moment().subtract(30, 'days'), // by default selected is "Last 30 days"
            ranges   : picker_ranges,
            locale   : locale
        },
        function (start, end) {
            var format = 'YYYY-MM-DD';
            $range_sms
                .data('date', start.format(format) + ' - ' + end.format(format))
                .html(start.format(BooklyL10n.mjsDateFormat) + ' - ' + end.format(BooklyL10n.mjsDateFormat));
        }
    );

    $('#sms_tabs [data-target="#' + BooklyL10n.current_tab + '"]').tab('show');

    // menu fix for WP 3.8.1
    $('#toplevel_page_ab-system > ul').css('margin-left', '0px');

    $('#ab_sms_notify_low_balance').on('change', function () {
        var $checkbox = $(this);
        $checkbox.hide().prev('img').show();
        $.get(ajaxurl, {action: 'ab_notify_low_balance', value: $checkbox.is(':checked') ? 1 : 0 }, function () {}, 'json').always(function () {
            $checkbox.show().prev('img').hide();
        });
    });

    $('#ajax-send-change-password').on('click', function (e) {
        e.preventDefault();
        var $form = $('#form-change-password');
        var new_password = $form.find('#new_password').val();
        if ($form.find('#old_password').val() != '') {
            if (new_password == $form.find('#new_password_repeat').val() && new_password != '') {
                $.ajax({
                    type        : 'POST',
                    url         : ajaxurl,
                    data        : $form.serialize(),
                    dataType    : 'json',
                    xhrFields   : { withCredentials: true },
                    crossDomain : 'withCredentials' in new XMLHttpRequest(),
                    success     : function (response) {
                        if (response.success) {
                            $('#modal_change_password').modal('hide');
                            $form.trigger('reset');
                        } else {
                            if(response.data && response.data.message) { alert(response.data.message); }
                        }
                    }
                });
            } else {
                alert(BooklyL10n.passwords_no_same);
            }
        } else {
            alert(BooklyL10n.input_old_password);
        }
    });

    /**
     * Notifications Tab.
     */
    var $phone_input = $('#admin_phone');
    if (BooklyL10n.intlTelInput.enabled) {
        $phone_input.intlTelInput({
            preferredCountries: [BooklyL10n.intlTelInput.country],
            defaultCountry: BooklyL10n.intlTelInput.country,
            geoIpLookup: function (callback) {
                $.get(ajaxurl, {action: 'ab_ip_info'}, function () {
                }, 'json').always(function (resp) {
                    var countryCode = (resp && resp.country) ? resp.country : '';
                    callback(countryCode);
                });
            },
            utilsScript: BooklyL10n.intlTelInput.utils
        });
    }
    $('#js-submit-notifications').on('click', function (e) {
        e.preventDefault();
        var ladda = Ladda.create(this);
        ladda.start();
        var $form = $(this).parents('form');
        $form.ab_sms_administrator_phone = BooklyL10n.intlTelInput.enabled ? $phone_input.intlTelInput('getNumber') : $phone_input.val();
        $form.submit();
    });
    $('#send_test_sms').on('click', function (e) {
        e.preventDefault();
        $.ajax({
            url         : ajaxurl,
            data        : { action: 'ab_send_test_sms', phone_number: BooklyL10n.intlTelInput.enabled ? $phone_input.intlTelInput('getNumber') : $phone_input.val() },
            dataType    : 'json',
            xhrFields   : { withCredentials: true },
            crossDomain : 'withCredentials' in new XMLHttpRequest(),
            success     : function (response) {
                if (response.message) {
                    alert(response.message);
                }
            }
        });
    });

    /**
     * Auto-Recharge Tab.
     */
    var $preapproval_create       = $('#ab-preapproval-create'),
        $preapproval_decline      = $('#ab-preapproval-decline'),
        $preapproval_form_init    = $('#ab-preapproval-form-init'),
        $preapproval_form_decline = $('#ab-preapproval-form-decline')
        ;
    $preapproval_create.on('click', function () {
        var ladda = Ladda.create(this);
        ladda.start();
        $.get(ajaxurl, {action: 'ab_show_preapproval', amount: $('#ab_auto_recharge_amount').val()}, function () {
        }, 'json').always(function (response) {
            if (response.success) {
                window.location.replace(response.data.paypal_preapproval);
            } else {
                ladda.stop();
                alert(response.data.message);
            }
        });
    });
    $preapproval_decline.on('click', function () {
        var ladda = Ladda.create(this);
        ladda.start();
        $.get(ajaxurl, {action: 'ab_decline_preapproval'}, function () {}, 'json')
            .always(function (response) {
                if (response.success) {
                    $preapproval_form_decline.hide();
                    $preapproval_form_init.show();
                }
                ladda.stop();
                alert(response.data.message);
            });
    });
    if (BooklyL10n.auto_recharge_balance) {
        $preapproval_form_init.hide();
    } else {
        $preapproval_form_decline.hide();
    }

    $('[data-toggle="popover"]').popover({
        html: true,
        placement: 'top',
        trigger: 'hover',
        template: '<div class="popover bookly-font-xs" style="width: 220px" role="tooltip"><div class="popover-arrow"></div><h3 class="popover-title"></h3><div class="popover-content"></div></div>'
    });
});