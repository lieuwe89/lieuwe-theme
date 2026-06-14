(function () {
    'use strict';

    var CFG = window.lieuweTeaching || {};
    var LABELS = {
        'spoon-carving':       'spoon carving',
        'japanese-lacquering': 'Japanese lacquering',
        'sandalmaking':        'sandalmaking',
        'general':             'general updates'
    };

    // Resolve a reCAPTCHA v3 token for an action; '' when reCAPTCHA isn't present.
    function token(action) {
        return new Promise(function (resolve) {
            if (!CFG.recaptchaKey || !window.grecaptcha || !window.grecaptcha.execute) {
                resolve('');
                return;
            }
            window.grecaptcha.ready(function () {
                window.grecaptcha.execute(CFG.recaptchaKey, { action: action })
                    .then(resolve)
                    .catch(function () { resolve(''); });
            });
        });
    }

    function postForm(form) {
        var data = new FormData(form);
        data.append('te_ajax', '1');
        return fetch(CFG.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
            .then(function (r) { return r.json(); });
    }

    function showFormError(form, msg) {
        var box = form.parentNode.querySelector('.te-form-error');
        if (!box) {
            box = document.createElement('p');
            box.className = 'te-form-error';
            box.setAttribute('role', 'alert');
            form.parentNode.insertBefore(box, form);
        }
        box.textContent = msg;
    }

    function interestLine(keys) {
        var names = keys.map(function (k) { return LABELS[k] || k; });
        if (names.length === 0) { return "I'll give you a shout the moment new dates go up."; }
        if (names.length === 1) { return "I'll let you know as soon as new " + names[0] + " dates go up."; }
        if (names.length === 2) { return "I'll let you know when new " + names[0] + " or " + names[1] + " dates go up."; }
        return "I'll give you a shout the moment new dates go up across the crafts you picked.";
    }

    // ---- Popup ----
    var lastFocus = null;
    function openPopup(email, interests) {
        lastFocus = document.activeElement;

        var modal = document.createElement('div');
        modal.className = 'te-modal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-label', 'Signup confirmed');

        var scrim = document.createElement('div');
        scrim.className = 'te-modal__scrim';
        modal.appendChild(scrim);

        var card = document.createElement('div');
        card.className = 'te-modal__card';
        modal.appendChild(card);

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'te-modal__close';
        close.setAttribute('aria-label', 'Close');
        close.textContent = '✕';
        card.appendChild(close);

        var check = document.createElement('div');
        check.className = 'te-modal__check';
        check.setAttribute('aria-hidden', 'true');
        check.textContent = '✓';
        card.appendChild(check);

        var h = document.createElement('h2');
        h.className = 'te-modal__title';
        h.textContent = "Right, you're on the list.";
        card.appendChild(h);

        var p = document.createElement('p');
        p.className = 'te-modal__text';
        p.appendChild(document.createTextNode(interestLine(interests) + ' '));
        if (email) {
            p.appendChild(document.createTextNode('I’ll write to '));
            var em = document.createElement('span');
            em.className = 'te-modal__email';
            em.textContent = email;
            p.appendChild(em);
            p.appendChild(document.createTextNode('.'));
        }
        card.appendChild(p);

        if (interests.length) {
            var chips = document.createElement('div');
            chips.className = 'te-modal__chips';
            interests.forEach(function (k) {
                var c = document.createElement('span');
                c.className = 'te-chip';
                c.textContent = LABELS[k] || k;
                chips.appendChild(c);
            });
            card.appendChild(chips);
        }

        var footer = document.createElement('div');
        footer.className = 'te-modal__footer';
        var ok = document.createElement('button');
        ok.type = 'button';
        ok.className = 'te-btn te-btn--primary';
        ok.textContent = 'Lovely, thanks';
        footer.appendChild(ok);
        var hint = document.createElement('span');
        hint.className = 'te-modal__hint';
        hint.textContent = 'No spam — just class dates.';
        footer.appendChild(hint);
        card.appendChild(footer);

        document.body.appendChild(modal);
        document.body.classList.add('te-modal-open');
        ok.focus();

        function destroy() {
            document.body.classList.remove('te-modal-open');
            modal.remove();
            document.removeEventListener('keydown', onKey);
            if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
        }
        function onKey(e) {
            if (e.key === 'Escape') {
                destroy();
            } else if (e.key === 'Tab') {
                // Trap focus between the only two focusables: close + ok.
                if (e.shiftKey && document.activeElement === close) { e.preventDefault(); ok.focus(); }
                else if (!e.shiftKey && document.activeElement === ok) { e.preventDefault(); close.focus(); }
            }
        }
        close.addEventListener('click', destroy);
        ok.addEventListener('click', destroy);
        scrim.addEventListener('click', destroy);
        card.addEventListener('click', function (e) { e.stopPropagation(); });
        document.addEventListener('keydown', onKey);
    }

    // ---- Signup ----
    function initSignup() {
        var form = document.querySelector('.te-signup');
        if (!form) { return; }
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (form.reportValidity && !form.reportValidity()) { return; }

            var email = (form.querySelector('input[name="te_email"]') || {}).value || '';
            var interests = Array.prototype.slice
                .call(form.querySelectorAll('input[name="te_interests[]"]:checked'))
                .map(function (i) { return i.value; });

            token('signup').then(function (t) {
                var field = form.querySelector('.te-token');
                if (field) { field.value = t; }
                postForm(form).then(function (res) {
                    if (res && res.success) {
                        var data = res.data || {};
                        var box = document.createElement('div');
                        box.className = 'te-confirm-inline';
                        box.setAttribute('role', 'status');
                        var msg = document.createElement('p');
                        msg.textContent = "Right, you're on the list. I'll be in touch when new dates go up.";
                        box.appendChild(msg);
                        form.parentNode.replaceChild(box, form);
                        openPopup(data.email || email, data.interests || interests);
                    } else {
                        showFormError(form, (res && res.data && res.data.message) || 'Something went wrong. Please try again.');
                    }
                }).catch(function () { showFormError(form, 'Network error — please try again.'); });
            });
        });
    }

    // ---- Booking ----
    function initBooking() {
        var form = document.querySelector('.te-booking');
        if (!form) { return; }
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (form.reportValidity && !form.reportValidity()) { return; }

            var name = (form.querySelector('input[name="te_name"]') || {}).value || '';

            token('booking').then(function (t) {
                var field = form.querySelector('.te-token');
                if (field) { field.value = t; }
                postForm(form).then(function (res) {
                    if (res && res.success) {
                        swapBookingConfirm(form, (res.data && res.data.name) || name);
                    } else {
                        showFormError(form, (res && res.data && res.data.message) || 'Something went wrong. Please try again.');
                    }
                }).catch(function () { showFormError(form, 'Network error — please try again.'); });
            });
        });
    }

    function swapBookingConfirm(form, name) {
        var first = (name || '').split(' ')[0];
        var titleEl = document.querySelector('.te-book__title');
        var classTitle = titleEl ? titleEl.textContent : '';
        var backEl = document.querySelector('.te-book__back');
        var main = form.closest('.te-book__main') || form.parentNode;

        var box = document.createElement('div');
        box.className = 'te-confirm';
        box.setAttribute('role', 'status');

        var check = document.createElement('div');
        check.className = 'te-confirm__check';
        check.setAttribute('aria-hidden', 'true');
        check.textContent = '✓';
        box.appendChild(check);

        var h = document.createElement('h2');
        h.className = 'te-confirm__title';
        h.textContent = 'Spot requested' + (first ? ', ' + first : '') + '.';
        box.appendChild(h);

        var p = document.createElement('p');
        p.textContent = "Thanks — I've noted your request" + (classTitle ? ' for ' + classTitle : '')
            + '. I hold spots by hand and will be in touch by email to confirm.';
        box.appendChild(p);

        var actions = document.createElement('div');
        actions.className = 'te-confirm__actions';
        var a = document.createElement('a');
        a.className = 'te-btn te-btn--primary';
        a.textContent = 'Back to all classes';
        a.href = backEl ? backEl.getAttribute('href') : '/teaching/';
        actions.appendChild(a);
        box.appendChild(actions);

        main.innerHTML = '';
        main.appendChild(box);
    }

    document.addEventListener('DOMContentLoaded', function () {
        initSignup();
        initBooking();
    });
})();
