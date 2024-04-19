/**
 * @author Levon Naghashyan
 * @site https://naghashyan.com
 * @mail levon@naghashyan.com
 * @year 2013-2022
 * @version 4.0.0
 */
import AjaxLoader from './AjaxLoader.js';
import NgsEvents from './NgsEvents.js';

let Dispatcher = {

    loadsObject: {},

    initialize: function () {
        if (NGS.getInitialLoad()) {
            NGS.nestLoad(NGS.getInitialLoad().load, NGS.getInitialLoad().params, true);
        }
        NgsEvents.fireEvent('onNGSLoad');
    },


    async componentLoad(loadObject, params) {
        let _url = "";
        if (loadObject.getUrl() !== "") {
            _url = this.computeUrl(loadObject.getUrl());
        } else {
            _url = this.computeUrl(loadObject.getPackage(), loadObject.getName(), loadObject.getNgsModule());
        }
        return await this.doRequest(_url, loadObject, params);
    },

    load: async function (loadObject, params) {
        let _url = "";
        if (loadObject.getUrl() !== "") {
            _url = this.computeUrl(loadObject.getUrl());

        } else {
            _url = this.computeUrl(loadObject.getPackage(), loadObject.getName(), loadObject.getNgsModule());
        }

        const responseJson = await this.doRequest(_url, loadObject, params);
        if (responseJson.ngsValidator) {
            loadObject.setArgs(ngsValidator);
            loadObject.onComplate(ngsValidator);
            return;
        }
        if (typeof (responseJson.nl)) {
            for (let p in responseJson.nl) {
                if (responseJson.nl.hasOwnProperty(p)) {
                    let nestedLoad = responseJson.nl[p];
                    for (let i = 0; i < nestedLoad.length; i++) {
                        NGS.setNestedLoad(p, nestedLoad[i].action, nestedLoad[i].params);
                    }
                }
            }
        }
        loadObject.setArgs(responseJson.params);
        loadObject.setPermalink(responseJson.pl);
        loadObject._updateContent(responseJson.html, responseJson.params);
        loadObject.onComplate(responseJson.params);

        //trigger event about page load
        NgsEvents.fireEvent('onNGSPageLoad', loadObject);
    },

    action: async function (actionObject, params) {
        let _url = this.computeUrl(actionObject.getPackage(), "do_" + actionObject.getName(), actionObject.getNgsModule());

        const responseJson = await this.doRequest(_url, actionObject, params);
        actionObject.setArgs(responseJson);
        actionObject.afterAction(responseJson);
        actionObject.onComplate(responseJson);
    },
    /**
     *
     * @param {object} actionObject
     * @param {object} params
     * @param {string} type
     */
    async call(actionObject, params, type = 'actions') {
        let requestType = '';
        if (type === 'actions') {
            requestType = 'do_';
        }
        let _url = this.computeUrl(actionObject.getPackage(), requestType + actionObject.getName(), actionObject.getNgsModule());
        return await this.doRequest(_url, actionObject, params);
    },

    doRequest: async function (_url, requestObject, params) {
        let options = {
            method: requestObject.getMethod(),
            paramsIn: requestObject.getParamsIn(),
            params: params,
            withoutLoader: requestObject.isWithoutLoader()
        };
        const response = await AjaxLoader.request(_url, options);
        try {

            if (response.status === 200) {
                return response.json();
            }
            const jsonResponse = await response.json();
            if (response.status === 400) {
                requestObject.onError(jsonResponse);
                return null;
            }
            if (response.status === 404) {
                requestObject.onNotFound(jsonResponse);
                throw new Error('Not found');
            }
            if (response.status === 401) {
                requestObject.onInvalidUser(jsonResponse);
                return null;
            }
            if (response.status === 403) {
                requestObject.onNoAccess(jsonResponse);
                return null;
            }
            if (response.status === 301) {
                requestObject.onRedirect(jsonResponse);
            }
            return null;
        } catch (e) {
            throw (e);
        }
    },


    /**
     * Method for computing request URLs depending on the current security level, baseUrl, package and command, mainly used internaly by the framework,
     *
     * @return computedUrl computed URL of the request
     */
    computeUrl: function () {
        let _package = arguments[0].replace(/\./g, '_');
        let command = "";
        if (arguments.length === 3) {
            command = arguments[1];
        }
        let module = null;
        if (arguments.length === 3) {
            module = arguments[2];
        }
        let dynContainer = "";
        if (NGS.getConfig().dynContainer !== "") {
            dynContainer = "/" + NGS.getConfig().dynContainer + "/";
        }

        if (NGS.getModule() != null && !module) {
            module = NGS.getModule();
        }
        return NGS.getHttpHost() + dynContainer + module + "/" + _package + "/" + command;
    }
};
export default Dispatcher;