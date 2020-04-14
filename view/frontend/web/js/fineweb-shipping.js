define([
    "jquery"
], function($) {
    "use strict";

    const maskPostcode = (postcode, event) => {
        return formataCampo(postcode, '00000000', event);
    }

    const formataCampo = (campo, Mascara, evento) => {
        let boleanoMascara,
            Digitato = evento.keyCode,
            exp = /\:|\-|\.|\/|\(|\)| /g,
            campoSoNumeros = campo.val().toString().replace(exp, ""),
            posicaoCampo = 0,
            NovoValorCampo = "",
            TamanhoMascara = campoSoNumeros.length;

        if (Digitato != 8) {
            for (let i = 0; i <= TamanhoMascara; i++) {
                boleanoMascara = ((Mascara.charAt(i) == ":") || (Mascara.charAt(i) == "-") || (Mascara.charAt(i) == ".") || (Mascara.charAt(i) == "/"))
                boleanoMascara = boleanoMascara || ((Mascara.charAt(i) == "(") ||
                    (Mascara.charAt(i) == ")") || (Mascara.charAt(i) == " "))
                if (boleanoMascara) {
                    NovoValorCampo += Mascara.charAt(i);
                    TamanhoMascara++;
                } else {
                    NovoValorCampo += campoSoNumeros.charAt(posicaoCampo);
                    posicaoCampo++;
                }
            }
            campo.val(NovoValorCampo);
            return true;
        } else {
            return true;
        }
    }

    const getShippingMethods = (postcode) => {
        let actionUrl = $('[name="simulate[actionUrl]"]').val(),
            productPost = $('#product_addtocart_form').serialize(),
            s = $("#shipping-estimate-results"),
            t = postcode,
            n = t.val();
        s.slideUp(), void 0 !== n && $.isNumeric(n) && 8 === n.length ? (t.removeClass("has-error"), $.ajax({
            type: "post",
            url: actionUrl,
            data: productPost,
            dataType: 'json',
            showLoader: !0,
            success: function(i) {
                let t = i;
                t.error ? s.html("<li>" + t.error.message + "</li>").slideDown() : $.map(t, function(i, t) {
                    let n = $('<li><span class="title">' + t + "</span></li>");
                    if (i.length > 0) {
                        var a = $("<ul></ul>");
                        $.map(i, function(s) {
                            let i = $('<li><span class="label">' + s.title + " - </span>" + s.price + "</li>");
                            "" != s.message && i.append("- " + s.message), a.append(i)
                        })
                    }
                    n.append(a), s.html(n).slideDown()
                })
            }
        })) : t.focus().addClass("has-error")
    }

    $(document).ready(function () {
        let shippingForm = $('.simulate-container'),
            btnCalculate = $('#btn-simulate'),
            postcode = $('input[name="simulate[postcode]"]');

        postcode.attr('maxlength', '8');
        postcode.keypress(function(e) {
            if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57))
                return false;
            maskPostcode($(this), e);
        });

        btnCalculate.on("click", function () {
            getShippingMethods(postcode)
        })
    })
});
