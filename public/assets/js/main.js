/**
 * Biblioteca Escolar - interações da interface.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        // Confirmação para ações destrutivas
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!window.confirm(form.getAttribute('data-confirm'))) {
                    event.preventDefault();
                }
            });
        });

        // Alertas somem sozinhos após alguns segundos
        document.querySelectorAll('.alert-dismissible').forEach(function (alerta) {
            window.setTimeout(function () {
                if (window.bootstrap && window.bootstrap.Alert) {
                    window.bootstrap.Alert.getOrCreateInstance(alerta).close();
                }
            }, 8000);
        });

        // Envio automático de filtros do catálogo
        document.querySelectorAll('[data-auto-submit]').forEach(function (campo) {
            campo.addEventListener('change', function () {
                if (campo.form) {
                    campo.form.submit();
                }
            });
        });
    });
})();
