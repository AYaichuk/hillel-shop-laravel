import './bootstrap';
import $ from 'jquery';

function getFields() {
    return $('#order-form').serializeArray().reduce(function (obj, item) {
        obj[item.name] = item.value;
        return obj;
    }, {});
}

function isEmptyFields() {
    const fields = getFields();

    for (const [_, value] of Object.entries(fields)) {
        if (value.length < 1) {
            return true;
        }
    }

    return false;
}

paypal.Buttons({
    onInit: function (data, actions) {
        if (isEmptyFields()) {
            actions.disable();
        }

        $(document).on('change', '#order-form', function() {
            if (!isEmptyFields()) {
                actions.enable();
            }
        });
    },
    onClick: function(data, actions) {
        if (isEmptyFields()) {
            alert('Please fill the form')
        }
    },
    createOrder: function (data, actions) {
        const errorClass = 'is-invalid';
        const fields = getFields();

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })

        return $.ajax({
            url: '/paypa/order/create',
            type: 'POST',
            dataType: 'json',
            data: JSON.stringify(fields),
            beforeSend: function() {
                $('.invalid-feedback').remove();
                $(`.${errorClass}`).removeClass(errorClass);
            },
            error: function(data) {
                const responseJson = data.responseJSON;
                console.log('error', responseJson)
            }
        });
    },

    // Call your server to finalize the transaction
    onApprove: function (data, actions) {
        return fetch('/demo/checkout/api/paypal/order/' + data.orderID + '/capture/', {
            method: 'post'
        }).then(function (res) {
            return res.json();
        }).then(function (orderData) {
            // Three cases to handle:
            //   (1) Recoverable INSTRUMENT_DECLINED -> call actions.restart()
            //   (2) Other non-recoverable errors -> Show a failure message
            //   (3) Successful transaction -> Show confirmation or thank you

            // This example reads a v2/checkout/orders capture response, propagated from the server
            // You could use a different API or structure for your 'orderData'
            var errorDetail = Array.isArray(orderData.details) && orderData.details[0];

            if (errorDetail && errorDetail.issue === 'INSTRUMENT_DECLINED') {
                return actions.restart(); // Recoverable state, per:
                // https://developer.paypal.com/docs/checkout/integration-features/funding-failure/
            }

            if (errorDetail) {
                var msg = 'Sorry, your transaction could not be processed.';
                if (errorDetail.description) msg += '\n\n' + errorDetail.description;
                if (orderData.debug_id) msg += ' (' + orderData.debug_id + ')';
                return alert(msg); // Show a failure message (try to avoid alerts in production environments)
            }

            // Successful capture! For demo purposes:
            console.log('Capture result', orderData, JSON.stringify(orderData, null, 2));
            var transaction = orderData.purchase_units[0].payments.captures[0];
            alert('Transaction ' + transaction.status + ': ' + transaction.id + '\n\nSee console for all available details');

            // Replace the above to show a success message within this page, e.g.
            // const element = document.getElementById('paypal-button-container');
            // element.innerHTML = '';
            // element.innerHTML = '<h3>Thank you for your payment!</h3>';
            // Or go to another URL:  actions.redirect('thank_you.html');
        });
    }
}).render('#paypal-button-container');
