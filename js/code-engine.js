console.log('🟢 Code Engine is running with the options: ', code_engine );

// #region Constants

const rest_url = code_engine.rest_url;

// #endregion Constants

// #region Dialog

(function ($) {
    $(window).load(function() {
        $('#action-result-dialog').dialog({
            title: 'Action Result',
            dialogClass: 'wp-dialog',
            autoOpen: false,
            draggable: false,
            width: 'auto',
            modal: true,
            resizable: false,
            closeOnEscape: true,
            position: {
                my: "center",
                at: "center",
                of: window
            },
            open: function () {
                $('.ui-widget-overlay').bind('click', function(){
                    $('#action-result-dialog').dialog('close');
                })
            },
            create: function () {
                $('.ui-dialog-titlebar-close').addClass('ui-button');
            },
        });
    });
})(jQuery);

// #endregion Dialog

// #region Main

document.addEventListener('DOMContentLoaded', function() {
    const registerButtons = document.querySelectorAll('.cdegn-register');
    const unregisterButtons = document.querySelectorAll('.cdegn-unregister');
    const refreshButtons = document.querySelectorAll('.cdegn-refresh');
    const checkQualityButtons = document.querySelectorAll('.cdegn-check-quality');

    const rest_nonce = document.querySelector('#_wpnonce_rest').value;

    // Register events to buttons
    registerButtons.forEach(function(element) {
        element.addEventListener('click', async function(e) {
            const snippet_id = e.target.dataset.id;
            const snippet_src = e.target.dataset.src;
            if (!snippet_id) {
                console.error('No snippet ID found');
                return;
            }
            if (!snippet_src) {
                console.error('No snippet src found');
                return;
            }

            const response = await postFetch('/register', { snippet_id, snippet_src });
            showResult(response.message);

            e.target.style.display = 'none';
            e.target.parentNode.querySelector('.cdegn-unregister').style.display = 'block';
            e.target.parentNode.querySelector('.cdegn-refresh').style.display = 'block';
            e.target.parentNode.querySelector('.cdegn-check-quality').style.display = 'block';
            e.target.closest('tr').querySelector('.function-overview').textContent = response.overview;
        });
    });

    unregisterButtons.forEach(function(element) {
        element.addEventListener('click', async function(e) {
            const snippet_id = e.target.dataset.id;
            const snippet_src = e.target.dataset.src;
            if (!snippet_id) {
                console.error('No snippet ID found');
                return;
            }
            if (!snippet_src) {
                console.error('No snippet src found');
                return;
            }

            const response = await postFetch('/unregister', { snippet_id, snippet_src });
            showResult(response.message);

            e.target.style.display = 'none';
            e.target.parentNode.querySelector('.cdegn-register').style.display = 'block';
            e.target.parentNode.querySelector('.cdegn-refresh').style.display = 'none';
            e.target.parentNode.querySelector('.cdegn-check-quality').style.display = 'none';
            e.target.closest('tr').querySelector('.function-overview').textContent = '';
        });
    });

    refreshButtons.forEach(function(element) {
        element.addEventListener('click', async function(e) {
            const snippet_id = e.target.dataset.id;
            const snippet_src = e.target.dataset.src;
            if (!snippet_id) {
                console.error('No snippet ID found');
                return;
            }
            if (!snippet_src) {
                console.error('No snippet src found');
                return;
            }

            const response = await postFetch('/refresh', { snippet_id, snippet_src });
            showResult(response.message);

            e.target.closest('tr').querySelector('.function-overview').textContent = response.overview;
        });
    });

    checkQualityButtons.forEach(function(element) {
        element.addEventListener('click', async function(e) {
            const snippet_id = e.target.dataset.id;
            const snippet_src = e.target.dataset.src;
            if (!snippet_id) {
                console.error('No snippet ID found');
                return;
            }
            if (!snippet_src) {
                console.error('No snippet src found');
                return;
            }

            showSpinner(snippet_id, snippet_src);

            const response = await postFetch('/check-quality', { snippet_id, snippet_src });

            hideSpinner(snippet_id, snippet_src);
            showResult(response.detail);
        });
    });

    document.querySelector('#btn-display-functions-json').addEventListener('click', async function() {
        const response = await getFetch('/functions');
        showResult("<?php esc_html_e( 'Success! Check the Developer Tools Console for the JSON content.', 'code-engine' ); ?>");
        console.log(response.functions);
    });

    /**
     * Show the result of an action.
     * @param {string} message
     * @returns {void}
     */
    const showResult = (message) => {
        (function ($) {
            if ($('#action-result-dialog').length > 0) {
                $('#action-result-dialog').find('p').html(message);
                $('#action-result-dialog').dialog('open');
            } else {
                alert(message);
            }
        })(jQuery);
    };

    /**
     * Show the spinner.
     * @param {string} snippet_id
     * @param {string} snippet_src
     * @returns {void}
     */
    const showSpinner = (snippet_id, snippet_src) => {
        const spinner = document.querySelector('.spinner[data-id="' + snippet_id + '"][data-src="' + snippet_src + '"]');
        if (spinner) {
            spinner.classList.add('is-active');
            document.querySelector('.wrap').querySelectorAll('input').forEach(function(element) {
                element.disabled = true;
            });
        }
    };

    /**
     * Hide the spinner.
     * @param {string} snippet_id
     * @param {string} snippet_src
     * @returns {void}
     */
    const hideSpinner = (snippet_id, snippet_src) => {
        const spinner = document.querySelector('.spinner[data-id="' + snippet_id + '"][data-src="' + snippet_src + '"]');
        if (spinner) {
            spinner.classList.remove('is-active');
            document.querySelector('.wrap').querySelectorAll('input').forEach(function(element) {
                element.disabled = false;
            });
        }
    };

    /**
     * Fetch data using POST method.
     * @param {string} path
     * @param {object} data
     * @returns {Promise<object>}
     */
    const postFetch = async function(path, data) {
        const response = await fetch(rest_url + path, {
            method: 'POST',
            headers: {
                'X-WP-Nonce': rest_nonce,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        const body = await response.json();
        if (!response.ok || body?.success === false ) {
            showResult(body.message);
            return;
        }
        return body;
    };

    /**
     * Fetch data using GET method.
     * @param {string} path
     * @returns {Promise<object>}
     */
    const getFetch = async function(path) {
        const response = await fetch(rest_url + path, {
            method: 'GET',
            headers: {
                'X-WP-Nonce': rest_nonce,
                'Content-Type': 'application/json',
            },
        });
        const body = await response.json();
        if (!response.ok || body?.success === false ) {
            showResult(body.message);
            return;
        }
        return body;
    };
});

// #endregion Main