define([
    "jquery",
    'mage/storage',
    'Magento_Checkout/js/model/error-processor'
], function ($,storage, errorProcessor) {
    'use strict';
    $.widget('mage.estimateRate', {
        options: {
        },
        _create: function () {
            var self = this;
            $('#deco-shipping-getrate').on('click', function (e) {
                self.mostrarLoading();
                var postcode = $(".product-info-main #deco-shipping-cep").val();
                postcode = self.apenasNumeros(postcode);

                if(!self.apenasNumerosTest(postcode)){
                    self.mensagemErro(1);
                    self.esconderLoading();
                    return;
                }

                if ($("body.page-product-configurable").length > 0 && $('input[name="selected_configurable_option"]').val() == "") {
                    self.mensagemErro(4);
                    self.esconderLoading();
                    return;
                }

                var id;
                if($('input[name="selected_configurable_option"]').val() != ""){
                    id = $('input[name="selected_configurable_option"]').val();
                } else {
                    id = $('input[name="product"]').val();
                }

                var qty = $(".product-info-main #qty").val();
                qty = qty.replace(/\D/g, '');
                var serviceUrl = 'rest/V1/shipping/estimate/'+id,
                payload = JSON.stringify({
                        address: {
                            'postcode': postcode
                        },
                        qty: qty 
                    }
                );
                self.getRate(payload,serviceUrl);
            });
        },
        mensagemErro: function (msg) {
            $(".deco-shipping-wrapper .error-message-"+msg).css("display", "initial");
            $(".deco-shipping-wrapper .error-message-"+msg).show("fast");
            setTimeout(function() {
                $(".deco-shipping-wrapper .error-message-"+msg).hide("slow");
            }, 3000);
        },
        formatarParaRealManual: function (numero) {
            const valorFormatado = numero.toFixed(2).replace('.', ',');
            const partes = valorFormatado.split(',');
            partes[0] = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            return `R$ ${partes.join(',')}`;
        },
        apenasNumerosTest: function(str) {
            return /^[0-9]+$/.test(str);
        },
        apenasNumeros: function(str) {
            return str.replace(/\D/g, '');
        },
        getRate: function (payload,serviceUrl) {
            storage.post(
            serviceUrl, payload, false
            ).done(
                function (result) {
                    var html;
                    var price;
                    var i;
                    html = "<table>";
                    html += "<tr><th>Descrição</th><th>Método - Tempo</th><th>Preço</th></tr>";

                    for(i=0;i<result.length;i++) {
                        price = this.formatarParaRealManual(result[i]["amount"]);
                        html += "<tr><td>"+result[i].carrier_title+"</td><td>"+result[i].method_title+"</td><td class='deco-shipping-price' >"+price+"</td></tr>";
                    }

                    html += "</table>";
                    $(".product-info-main #deco-shipping-table").html(html);
                    this.esconderLoading();
                }.bind(this)
            ).fail(
                function (response) {
                    this.mensagemErro(2);
                    this.esconderLoading();
                    return;
                    errorProcessor.process(response);
                }.bind(this)
            );
        },
        mostrarLoading: function() {
            document.getElementById('loading-overlay').style.display = 'flex';
        },
        esconderLoading: function() {
            document.getElementById('loading-overlay').style.display = 'none';
        }
    });
    return $.mage.estimateRate;
});