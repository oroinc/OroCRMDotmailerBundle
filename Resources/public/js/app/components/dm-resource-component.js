/*jslint nomen: true*/
/*global define*/
define(function (require) {
    'use strict';

    var _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        $ = require('jquery');

    return function (options) {
        var $source = options._sourceElement,
            $username = $('input.dm-username'),
            $password = $('input.dm-password'),
            $btn = $source.find('button'),
            $status = $source.find('.connection-status'),
            $pingHolder = $source.find('.ping-holder');

        var onError = function (message) {
            message = message || __('orocrm.mailchimp.integration_transport.api_key.check.message');
            $status.removeClass('alert-info')
                .addClass('alert-error')
                .html(message)
        };

        var localCheckCredentials = function () {
            if ($username.val().length && $password.val().length && $password.valid()) {
                $pingHolder.show();
            } else {
                $pingHolder.hide();
            }
        };

        localCheckCredentials();
        $username.on('keyup', function () {
            localCheckCredentials();
        });
        $password.on('keyup', function () {
            localCheckCredentials();
        });

        $btn.on('click', function () {
            if ($username.valid() && $password.valid()) {
                $.getJSON(
                    options.pingUrl,
                    {'username': $username.val(), 'password': $password.val()},
                    function (response) {
                        if (_.isUndefined(response.error)) {
                            $status.removeClass('alert-error')
                                .addClass('alert-info')
                                .html(response.msg);
                        } else {
                            onError(response.error);
                        }
                    }
                ).always(
                    function () {
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
