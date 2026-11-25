/**
 * Biblioteca Escolar - tema, aparências e interações.
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
            button.setAttribute('aria-pressed', dark ? 'true' : 'false');
        });
    }
    function applyAppearance(appearance) {
        var selected = APPEARANCES.indexOf(appearance) >= 0 ? appearance : 'roxo';
        document.documentElement.setAttribute('data-appearance', selected);
        try { localStorage.setItem(APPEARANCE_KEY, selected); } catch (error) {}
        document.querySelectorAll('[data-appearance-select]').forEach(function (select) { select.value = selected; });
    }
    function initialize() {
        if (document.documentElement.dataset.beInitialized === 'true') return;
        document.documentElement.dataset.beInitialized = 'true';
        var savedTheme = 'light', savedAppearance = 'roxo';
        try {
            var a = localStorage.getItem(APPEARANCE_KEY);
            if (APPEARANCES.indexOf(a) >= 0) savedAppearance = a;
        } catch (error) {}
        applyAppearance(savedAppearance);
        applyTheme('light');

        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-theme-toggle]');
            if (button) {
                event.preventDefault();
                var current = document.documentElement.getAttribute('data-theme');
                applyTheme(current === 'dark' ? 'light' : 'dark');
            }
            var passwordButton = event.target.closest('[data-password-toggle]');
            if (passwordButton) {
                var input = document.getElementById(passwordButton.getAttribute('data-password-toggle'));
                if (!input) return;
                var showing = input.type === 'password';
                input.type = showing ? 'text' : 'password';
                var icon = passwordButton.querySelector('i');
                if (icon) icon.className = showing ? 'bi bi-eye-slash' : 'bi bi-eye';
                passwordButton.setAttribute('aria-label', showing ? 'Ocultar senha' : 'Mostrar senha');
            }
        });
        document.addEventListener('change', function (event) {
            if (event.target.matches('[data-appearance-select]')) applyAppearance(event.target.value);
            if (event.target.matches('[data-auto-submit]') && event.target.form) event.target.form.submit();
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
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
    else initialize();
})();
