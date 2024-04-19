/**
 *
 * this helper Object handle
 * all ajax request
 *
 * @author Levon Naghashyan
 * @site http://naghashyan.com
 * @mail levon@naghashyan.com
 * @year 2010-2022
 * @package ngs.framework
 * @version 2.0.0
 */
import './NGS.js';
//import {fetch as fetchPolyfill} from 'https://cdn.jsdelivr.net/npm/whatwg-fetch@3.6.2/+esm'

let AjaxLoader = {

    defaultHeaders: {},
    defaultOptions: {
        method: "GET",
        timeout: 60000,
        paramsIn: "query",
        params: {},
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            accept: null
        },
        crossDomain: false
    },

    addDefaultHeader(name, value) {
        this.defaultHeaders[name] = value;
    },

    /**
     * Method for ajax request handler
     *
     * @param {string} _url
     * @param  {object} _options
     *
     */

    request: async function (_url, _options) {
        return new Promise(async (resolve, reject) => {

            if (!window.navigator.onLine) {
                reject('offline');
                return;
            }
            const options = {...this.defaultOptions, ..._options};
            if (!options.withoutLoader) {
                NGS.showAjaxLoader();
            }
            let sendingData = null;
            let fetchData = {
                method: options.method.toUpperCase(),
                timeout: options.timeout,
                headers: {}
            };
            const headersArr = {...options.headers, ...this.defaultHeaders};
            for (let key in headersArr) {
                if (!headersArr.hasOwnProperty(key)) {
                    continue;
                }
                fetchData.headers[key] = headersArr[key];
            }
            if (options.method.toUpperCase() === 'DELETE' || options.method.toUpperCase() === 'GET'
                || options.paramsIn === "query" || options.method.toUpperCase() === 'DOWNLOAD') {
                if (options.params) {
                    _url = _url + "?" + new URLSearchParams(options.params);
                }
                if (options.method.toUpperCase() === 'DOWNLOAD') {
                    NGS.hideAjaxLoader();
                    window.location = _url;
                    return;
                }
            } else if (options.paramsIn === "formData") {
                if (options.params instanceof FormData) {
                    sendingData = options.params;
                } else {
                    sendingData = new FormData();
                    for (let i in options.params) {
                        if (options.params.hasOwnProperty(i)) {
                            sendingData.set(i, options.params[i]);
                        }
                    }
                }
                fetchData.body = sendingData;
            } else if (options.paramsIn === "body") {
                fetchData.body = JSON.stringify(options.params);
            }
            try {
            //    const response = fetchPolyfill(_url, fetchData);
                const response = fetch(_url, fetchData);
                if (!options.withoutLoader) {
                    NGS.hideAjaxLoader();
                }
                resolve(response);
            } catch (e) {
                reject(e);
            }

        });
    }

};
export default AjaxLoader;