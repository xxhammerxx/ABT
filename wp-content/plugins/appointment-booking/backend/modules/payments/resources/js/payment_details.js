jQuery(function($) {
    var $modal   = $('#bookly-payment-details-modal'),
        $body    = $modal.find('.modal-body'),
        spinner  = $body.html();

    $modal
        .on('show.bs.modal', function (e) {
            var payment_id = $(e.relatedTarget).data('payment_id');
            $.ajax({
                url:      ajaxurl,
                data:     {action: 'ab_get_payment_details', payment_id: payment_id},
                dataType: 'json',
                success:  function (response) {
                    if (response.success) {
                        $body.html(response.data.html);
                    }
                }
            });
        })
        .on('hidden.bs.modal', function () {
            $body.html(spinner);
            if (($("#bookly-appointment-dialog").data('bs.modal') || {isShown: false}).isShown) {
                $('body').addClass('modal-open');
            }
        });
});