define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const $ = require('jquery');

    return function(options) {
        const $source = options._sourceElement;
        const $username = $('input.dm-username');
        const $password = $('input.dm-password');
        const $btn = $source.find('button');
        const $status = $source.find('.connection-status');
        const $pingHolder = $source.find('.ping-holder');

        const onError = function(message) {
            message = message || __('oro.dotmailer.integration_transport.api_key.check.message');
            $status.removeClass('alert-info')
                .addClass('alert-error')
                .html(message);
        };

        const localCheckCredentials = function() {
            if ($username.val().length && $password.val().length && $password.valid()) {
                $pingHolder.show();
            } else {
                $pingHolder.hide();
            }
        };

        localCheckCredentials();
        $username.on('keyup', function() {
            localCheckCredentials();
        });
        $password.on('keyup', function() {
            localCheckCredentials();
        });

        $btn.on('click', function() {
            if ($username.valid() && $password.valid()) {
                $.getJSON(
                    options.pingUrl,
                    {username: $username.val(), password: $password.val()},
                    function(response) {
                        if (_.isUndefined(response.error)) {
                            $status.removeClass('alert-error')
                                .addClass('alert-info')
                                .html(response.msg);
                        } else {
                            onError(response.error);
                        }
                    }
                ).always(
                    function() {
                        $status.show();
                    }
                ).fail(
                    onError
                );
            } else {
                $status.show();
                onError();
            }
        });
    };
});
