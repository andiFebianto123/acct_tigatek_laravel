
function siaOps() {
    this.setAttribute = function (name, value) {
        if (this.attributes.hasOwnProperty(name)) {
            delete this.attributes[name];
        }
        if (typeof value === 'function') {
            this.attributes[name] = value.bind(this);
            this.attributes[name] = this.attributes[name]();
        } else {
            this.attributes[name] = value;
        }
        return this;
    }

    this.getAttribute = function (name) {
        if (this.attributes.hasOwnProperty(name)) {
            return this.attributes[name];
        }
        return null;
    }

    this.getAllAttributes = function () {
        return this.attributes;
    }

    this.addModules = function (name, callback) {
        if (this.modules.hasOwnProperty(name)) {
            delete this.modules[name];
            this.modules[name] = callback;
        } else {
            this.modules[name] = callback;
        }
        return this;
    }

    this.addMethods = function (name, callback) {
        if (this.methods.hasOwnProperty(name)) {
            delete this.methods[name];
            this.methods[name] = callback;
        }
        this.methods[name] = callback;
        return this;
    }

    this.callMethod = function (methodName) {
        if (this.methods.hasOwnProperty(methodName)) {
            let callable = this.methods[methodName].bind(this);
            callable()
        }
    }

    this.callModule = function (moduleName) {
        if (this.modules.hasOwnProperty(moduleName)) {
            let callable = this.modules[moduleName].bind(this);
            return callable;
        }
        return null;
    }

    this.loadScript = function (src, options = {}) {
        const config = {
            position: 'body',
            async: true,
            defer: false,
            callback: null,
            dependencies: [],
            ...options
        };

        const scripts = Array.isArray(src) ? src : [src];
        const allScripts = [...config.dependencies, ...scripts];

        const loadPromises = allScripts.map(script => {
            return new Promise((resolve, reject) => {
                // Cek apakah script sudah dimuat sebelumnya
                if (document.querySelector(`script[src="${script}"]`)) {
                    resolve();
                    return;
                }

                const scriptEl = document.createElement('script');
                scriptEl.src = script;
                scriptEl.async = config.async;
                scriptEl.defer = config.defer;
                scriptEl.onload = resolve;
                scriptEl.onerror = reject;

                // Tambahkan ke DOM
                if (config.position === 'head') {
                    document.head.appendChild(scriptEl);
                } else {
                    document.body.appendChild(scriptEl);
                }
            });
        });

        // Gabungkan semua promise
        return Promise.all(loadPromises)
            .then(() => {
                if (config.callback && typeof config.callback === 'function') {
                    config.callback.call(this);
                }
                return true;
            })
            .catch(error => {
                console.error('Script loading failed:', error);
                throw error;
            });
    };

    this.loadCSS = function (href, options = {}) {
        const config = {
            position: 'head',
            callback: null,
            ...options
        };

        const stylesheets = Array.isArray(href) ? href : [href];

        const loadPromises = stylesheets.map(style => {
            return new Promise((resolve, reject) => {
                if (document.querySelector(`link[href="${style}"]`)) {
                    resolve();
                    return;
                }

                const linkEl = document.createElement('link');
                linkEl.rel = 'stylesheet';
                linkEl.href = style;
                linkEl.onload = resolve;
                linkEl.onerror = reject;

                document[config.position].appendChild(linkEl);
            });
        });

        return Promise.all(loadPromises)
            .then(() => {
                if (config.callback && typeof config.callback === 'function') {
                    config.callback.call(this);
                }
                return true;
            });
    };


}

siaOps.prototype.attributes = {};
siaOps.prototype.modules = {};
siaOps.prototype.methods = {};
window.SIAOPS = new siaOps;

function formatTimeAgo(timestamp) {
    const now = new Date();
    const time = new Date(timestamp); // Konversi string ke objek Date
    if (isNaN(time.getTime())) {
        return "Tanggal tidak valid"; // Validasi jika format tidak benar
    }

    const diff = now - time;

    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (seconds < 60) {
        return "Baru saja";
    } else if (minutes < 60) {
        return `${minutes} menit yang lalu`;
    } else if (hours < 24) {
        return `${hours} jam yang lalu`;
    } else if (days <= 3) {
        return `${days} hari yang lalu`;
    } else {
        return time.toLocaleDateString("id-ID", {
            year: "numeric",
            month: "long",
            day: "numeric",
            // hour: "2-digit",
            // minute: "2-digit",
        });
    }
}


function formatRupiah(angka, prefix = '') {
    let isNegative = false;

    if (typeof angka === 'number' && angka < 0) {
        isNegative = true;
        angka = Math.abs(angka);
    }

    // Bulatkan ke puluhan terdekat
    angka = Math.round(angka / 10) * 10;

    const numberString = angka.toString();
    const split = numberString.split(',');
    let sisa = split[0].length % 3;
    let rupiah = split[0].substr(0, sisa);
    const ribuan = split[0].substr(sisa).match(/\d{3}/g);

    if (ribuan) {
        const separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }

    rupiah = split[1] !== undefined ? rupiah + ',' + split[1] : rupiah;

    return (prefix ? prefix + ' ' : '') + (isNegative ? '-' : '') + rupiah;
}

function formatIdr(angka) {
    const formatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR'
    });

    let hasilFormat = formatter.format(angka);
    let tanpaRp = hasilFormat.replace('Rp', '').trim();

    return tanpaRp;
}



function notificationPanel() {
    this.notifIncrement = 0;

    this.actionNotification = function (id, url) {
        let url_update = $('#panel-notification').data('url-notification-redirect');
        window.location.href = url_update + '/' + id;
    }

    this.reloadNotification = function () {
        var url = $('#panel-notification').data('url-notification');
        var notif = this;
        $.ajax({
            url: url,
            type: 'POST',
            data: {},
            dataType: 'json',
            success: function (data) {
                var strNotif = ``;
                data.list.forEach((val, index) => {
                    strNotif += `
                        <div class="dropdown-item" onclick="notificationPanel.actionNotification(${val.id}, '${val.url}')" data-url-action="${val.url}" style="${(val.is_read === "0") ? 'background: #fff3cd !important;' : ''}">
                            <span class="icon bg-success p-2 text-white btn-round"><i class="fa fa-comment"></i></span>
                            <div class="content-wrapper">
                                <div class="content">${val.title}</div>
                                <div class="time">${formatTimeAgo(val.created_at)}</div>
                            </div>
                        </div>
                    `;
                })
                $('#notiv-body').html(strNotif);
                notif.notifIncrement = data.total_notif_no_read;
                // $('#title-new-notification').html(`You have ${notif.notifIncrement} new notification`);
                $('.notification').html(notif.notifIncrement);
                if (notif.notifIncrement > 0) {
                    $('#title-page').html(`(${notif.notifIncrement}) PT Data Utama Dinamika`);
                }
            },
            error: function (xhr, status, error) {
                alert('Cannot load data account contact');
            }
        });
    }
    this.updateNotification = function (message) {
        this.notifIncrement++;
        // $('#title-new-notification').html(`You have ${this.notifIncrement} new notification`);
        $('.notification').html(this.notifIncrement);
        $('#title-page').html(`(${this.notifIncrement}) PT Data Utama Dinamika`);
        $('#notiv-body').prepend(`
            <div class="dropdown-item" onclick="notificationPanel.actionNotification(${message.data.id}, '${message.data.url}')" data-url-action="${message.data.url}" style="background: #fff3cd;">
                <span class="icon bg-success p-2 text-white btn-round"><i class="fa fa-comment"></i></span>
                <div class="content-wrapper">
                    <div class="content">${message.notification.title}</div>
                    <div class="time">${formatTimeAgo(new Date())}</div>
                </div>
            </div>
        `);
    }

    this.triggerBtnNotification = function (data_check) {
        if (data_check.length > 0) {
            $('#unread-btn').show();
            $('#read-btn').show();
            $('#unread-btn').html(`<i class="fa fa-eye-slash"></i> (${data_check.length}) Unread`);
            $('#read-btn').html(`<i class="fa fa-eye"></i> (${data_check.length}) Read`);
        } else {
            $('#unread-btn').hide();
            $('#read-btn').hide();
        }
    }
}

class MyEventEmitter {
    constructor() {
        this.events = {};
    }

    on(eventName, listener) {
        if (!this.events[eventName]) this.events[eventName] = [];
        this.events[eventName].push(listener);
    }

    emit(eventName, data) {
        const listeners = this.events[eventName];
        if (listeners) {
            listeners.forEach(listener => listener(data));
        }
    }
}

window.eventEmitter = new MyEventEmitter();


function OpenCreateFormModal(attr = {}) {
    var config = {
        modal: {},
        ...attr,
    };

    $(`${config.modal.id} .modal-body`).html('loading...');
    if (config.modal.title != undefined) {
        $(`${config.modal.id} .modal-title`).html(config.modal.title);
    }
    $.ajax({
        url: config.route,
        type: 'GET',
        typeData: 'json',
        success: function (data) {
            $(`${config.modal.id} .modal-body`).html(data.html);
            if (config.modal.action != undefined) {
                $(`${config.modal.id} #form-create`).attr('action', config.modal.action);
            }
        },
        error: function (xhr, status, error) {
            console.error(xhr);
            alert('An error occurred while loading the create form.');
        }
    });
}

function OpenEditFormModal(attr = {}) {
    var config = {
        modal: {},
        ...attr,
    };

    $(`${config.modal.id} .modal-body`).html('loading...');
    if (config.modal.title != undefined) {
        $(`${config.modal.id} .modal-title`).html(config.modal.title);
    }
    $.ajax({
        url: config.route,
        type: 'GET',
        typeData: 'json',
        success: function (data) {
            $(`${config.modal.id} .modal-body`).html(data.html);
            if (config.modal.action != undefined) {
                $(`${config.modal.id} #form-edit`).attr('action', config.modal.action);
            }
        },
        error: function (xhr, status, error) {
            console.error(xhr);
            alert('An error occurred while loading the create form.');
        }
    });
}

function setLoadingButton(selector, isLoading, options = {}) {
    const buttons = document.querySelectorAll(selector);
    const loadingText = options.loadingText || 'Loading...';
    const spinnerHTML = options.spinnerHTML || '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ';

    buttons.forEach(button => {
        if (isLoading) {
            if (!button.dataset.originalHtml) {
                button.dataset.originalHtml = button.innerHTML;
            }

            button.innerHTML = spinnerHTML + loadingText;
            button.disabled = true;
        } else {
            if (button.dataset.originalHtml) {
                button.innerHTML = button.dataset.originalHtml;
            }
            button.disabled = false;
        }
    });
}

function forEachFlexible(data, callback) {
    if (Array.isArray(data)) {
        data.forEach((value, index) => callback(index, value));
    } else if (typeof data === 'object' && data !== null) {
        Object.entries(data).forEach(([key, value]) => callback(key, value));
    } else {
        console.warn('Data bukan array atau object');
    }
}

function hideModal(modal_id) {
    document.querySelector('#' + modal_id + ' button.btn-close').click();
}

var callApi = function (method, uri, payload, header) {
    let parameter = {
        method: (method == 'DOWNLOAD') ? 'POST' : method,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
    }
    if (method == 'POST' || method == 'DOWNLOAD') {
        parameter.body = (payload instanceof FormData) ? payload : JSON.stringify(payload);
    } else if (method == 'GET') {
        let newUri = uri + '?' + $.param(payload);
        uri = newUri;
    }

    if (payload instanceof FormData) {
        // parameter.headers['Accept'] = 'application/json';
        // parameter.headers['Content-Type'] = 'multipart/form-data';
    } else {
        parameter.headers['Content-Type'] = 'application/json;charset=utf-8';
    }

    const api = fetch(uri, parameter).then((response) => {
        if (response.ok) {
            return response;
        }
        let error = (response.status == 500 || response.status == 404) ? 'Internet lost connection !' : response.json();
        return Promise.reject(error);
    })
    return api;
}

var API_REQUEST = async function (method, url, payload, header = {}) {
    let errors, response = null;
    await callApi(method, url, payload, header)
        .then(res => {
            if (method == 'DOWNLOAD') {
                response = res.blob();
            } else {
                response = res.json();
            }
            return response;
        })
        .catch(err => errors = err);
    return { errors, response };
}

function getInputNumber(selected) {
    let value = '';
    value = $(selected).val() || 0;
    return isNaN(value) ? 0 : parseFloat(value);
}

function setInputNumber(selected, value) {
    if (value === null) {
        return;
    }
    let str = value.toString();

    str = str.replace(/\.00$/, "");

    if (!isNaN(str) && str.trim() !== "") {
        str = Number(str);
    }
    $(selected).val(str).trigger('input');
}

function setInputNumber2(selected, value) {
    let nominal = formatIdr(value);
    $(selected).val(nominal).trigger('input');
}

function MakeParamUrl(obj, prefix = "&") {
    return Object.entries(obj)
        .map(([k, v]) => `${prefix}${k}=${encodeURIComponent(v)}`)
        .join("");
}

function generateDataTableParams(values) {
    const params = new URLSearchParams();

    if (values == undefined) {
        return '';
    }

    values.forEach((val, i) => {
        params.append(`columns[${i}][search][value]`, val || "");
    });

    return params.toString();
}

