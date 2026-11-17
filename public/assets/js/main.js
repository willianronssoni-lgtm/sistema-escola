/**
 * Biblioteca Escolar - aparência, tema e interações da interface.
 */
(function () {
    'use strict';

    var THEME_KEY = 'biblioteca-escolar-theme';
    var APPEARANCE_KEY = 'biblioteca-escolar-appearance';
    var APPEARANCES = ['roxo', 'azul', 'branco', 'dourado', 'laranja', 'bordo', 'esmeralda'];

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

    function applyAppearance(appearance) {
        var selected = APPEARANCES.indexOf(appearance) >= 0 ? appearance : 'roxo';
        document.documentElement.setAttribute('data-appearance', selected);
        try { localStorage.setItem(APPEARANCE_KEY, selected); } catch (error) {}
        document.querySelectorAll('[data-appearance-select]').forEach(function (select) {
            select.value = selected;
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var savedTheme = 'light';
        var savedAppearance = 'roxo';
        try {
            var storedTheme = localStorage.getItem(THEME_KEY);
            var storedAppearance = localStorage.getItem(APPEARANCE_KEY);
            if (storedTheme === 'light' || storedTheme === 'dark') savedTheme = storedTheme;
            if (APPEARANCES.indexOf(storedAppearance) >= 0) savedAppearance = storedAppearance;
        } catch (error) {}
        applyAppearance(savedAppearance);
        applyTheme(document.documentElement.getAttribute('data-theme') || savedTheme);

        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                var current = document.documentElement.getAttribute('data-theme');
                applyTheme(current === 'dark' ? 'light' : 'dark');
            });
        });
        document.querySelectorAll('[data-appearance-select]').forEach(function (select) {
            select.addEventListener('change', function () { applyAppearance(select.value); });
        });

        document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.getElementById(button.getAttribute('data-password-toggle'));
                if (!input) return;
                var showing = input.type === 'password';
                input.type = showing ? 'text' : 'password';
                var icon = button.querySelector('i');
                if (icon) icon.className = showing ? 'bi bi-eye-slash' : 'bi bi-eye';
                button.setAttribute('aria-label', showing ? 'Ocultar senha' : 'Mostrar senha');
            });
        });

        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!window.confirm(form.getAttribute('data-confirm'))) event.preventDefault();
            });
        });
        document.querySelectorAll('.alert-dismissible').forEach(function (alerta) {
            window.setTimeout(function () {
                if (window.bootstrap && window.bootstrap.Alert) window.bootstrap.Alert.getOrCreateInstance(alerta).close();
            }, 8000);
        });
        document.querySelectorAll('[data-auto-submit]').forEach(function (campo) {
            campo.addEventListener('change', function () { if (campo.form) campo.form.submit(); });
        });
    });
})();