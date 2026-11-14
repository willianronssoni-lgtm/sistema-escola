/**
 * Biblioteca Escolar - interações da interface e alternância de tema.
 */
(function () {
    'use strict';

    var THEME_KEY = 'biblioteca-escolar-theme';

    function applyTheme(theme) {
        var selected = theme === 'dark' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', selected);
        try { localStorage.setItem(THEME_KEY, selected); } catch (error) {}
        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            var icon = button.querySelector('i');
            var label = button.querySelector('[data-theme-label]');
            var dark = selected === 'dark';
            if (icon) icon.className = dark ? 'bi bi-sun' : 'bi bi-moon-stars';
            if (label) label.textContent = dark ? 'Modo claro' : 'Modo escuro';
            button.setAttribute('aria-label', dark ? 'Ativar modo claro' : 'Ativar modo escuro');
            button.setAttribute('title', dark ? 'Ativar modo claro' : 'Ativar modo escuro');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var savedTheme = 'light';
        try {
            var stored = localStorage.getItem(THEME_KEY);
            if (stored === 'light' || stored === 'dark') savedTheme = stored;
        } catch (error) {}
        applyTheme(document.documentElement.getAttribute('data-theme') || savedTheme);

        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                var current = document.documentElement.getAttribute('data-theme');
                applyTheme(current === 'dark' ? 'light' : 'dark');
            });
        });

        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!window.confirm(form.getAttribute('data-confirm'))) event.preventDefault();
            });
        });

        document.querySelectorAll('.alert-dismissible').forEach(function (alerta) {
            window.setTimeout(function () {
                if (window.bootstrap && window.bootstrap.Alert) {
                    window.bootstrap.Alert.getOrCreateInstance(alerta).close();
                }
            }, 8000);
        });

        document.querySelectorAll('[data-auto-submit]').forEach(function (campo) {
            campo.addEventListener('change', function () {
                if (campo.form) campo.form.submit();
            });
        });
    });
})();
