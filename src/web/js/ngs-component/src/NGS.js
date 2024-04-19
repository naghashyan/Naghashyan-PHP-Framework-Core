/**
 * Base ngs object
 * for static function that will
 * vissible from any classes
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site https://naghashyan.com
 * @year 2014-2019
 * @package ngs.framework
 * @version 4.0.0
 *
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distribeed with this source code.
 *
 *
 */
import AbstractAction from "./AbstractAction.js";
import Dispatcher from "./Dispatcher.js";
import {reactive} from "./libs/vue.esm-browser.prod.min.js";

const NGS = {
    namespace: "",
    loadsContainer: {}, //private attribute for collect all loads
    actionsContainer: {}, //private attribute for collect all actions
    nestedLoads: {}, //private attribute for collect all actions
    initialLoads: {
        "load": null,
        "params": {}
    }, //private attribute for collect initial loads (used in ngs mode)
    module: null,
    _sessionId: null,
    environment: 'production',
    tmstDiff: 0,
    inited: false,
    _host: "",
    _moduleHost: "",
    _path: "",
    _jsPublicDir: 'js',
    _componentTampltePath: '/components/',
    _activeLoads: {},
    _globals: null,
    config: {
        defaultIndicator: false,//element id(false|String)
        initialLoad: "",
        initialLoadParams: {},
        dynContainer: "dyn",
        ajaxLoader: false
    },

    initialize: function () {
        Node.prototype.load = async function (loadName, params, insertionMode = "override") {
            const NgsLoadObject = await NGS.getNGSItemObjectByNameAndType(loadName, "load");
            NgsLoadObject.setContainer(this);
            NgsLoadObject.setInsertionMode(insertionMode);
            NgsLoadObject.load(params, this);
            return NgsLoadObject;
        };
        Node.prototype.nestLoad = async function (loadName, params, insertionMode = "override") {
            const NgsLoadObject = await NGS.getNGSItemObjectByNameAndType(loadName, "load");
            NgsLoadObject.setContainer(this);
            NgsLoadObject.setInsertionMode(insertionMode);
            NgsLoadObject.nestLoad(params, this);
            return NgsLoadObject;
        };
        this._initUtilities();
    },

    setEnvironment(environment) {
        this.environment = environment;
    },

    getEnvironment() {
        return this.environment;
    },

    /**
     * Method for creating load object
     *
     *
     */
    getConfig: function () {
        if (typeof (NGS.config) !== "undefined") {
            return NGS.config;
        }
        return {};
    },


    /**
     * Method for run selected load
     *
     * @param {string} loadName
     * @param {Object} params
     * @param {Object} callback
     * @param {Object} childLoadParams
     *
     */
    async load(loadName, params, callback, childLoadParams) {
        if (!childLoadParams) {
            childLoadParams = null;
        }
        let ngsLoadObject = await this.getNGSItemObjectByNameAndType(loadName, "load", childLoadParams);
        this.nestedLoads = {};
        let _loadCallbackFunction = function () {
            onCompleteCallback.forEach(calback => {
                calback.apply(this, arguments);
            })
        };
        let onCompleteCallback = [ngsLoadObject.onComplate];
        if (typeof (callback) === "function") {
            onCompleteCallback.push(callback);
        }
        ngsLoadObject.onComplate = _loadCallbackFunction;
        if (typeof (callback) === "object") {
            ngsLoadObject = Object.assign(laodObj, callback);
        }
        if (childLoadParams) {
            ngsLoadObject.setChildLoadParams(childLoadParams);
        }
        ngsLoadObject.load(params);

        return ngsLoadObject;
    },

    /**
     * Method for running nested loads
     *
     * @param {String} loadName
     * @param {Object} params
     * @param {Boolean} updateContent
     *
     */
    async nestLoad(loadName, params, updateContent = true) {
        const NgsLoadObject = await this.getNGSItemObjectByNameAndType(loadName, 'load');
        NgsLoadObject.setContainer(this);
        NgsLoadObject.nestLoad(params, null, updateContent);
        return NgsLoadObject;
    },

    /**
     * Method for running nested loads
     *
     */
    nestLoads: function () {
        try {
            for (let i = 0; i < this.nestedLoads.length; i++) {
                let nestLoad = this.nestedLoads[i];
                this.getNGSItemObjectByNameAndType('load', nestLoad['load']).nestLoad(nestLoad['parent'], nestLoad['params']);
            }
        } catch (e) {
            throw e;
        }
    },

    /**
     * Method for running nested loads
     *
     * @param parent {String}
     * @param loadName {String}
     * @param params {Object}
     *
     */
    setNestedLoad: function (parent, loadName, params) {
        if (typeof (this.nestedLoads[parent]) === 'undefined') {
            this.nestedLoads[parent] = [];
        }
        this.nestedLoads[parent].push({
            "parent": parent,
            "load": loadName,
            "params": params
        });
    },

    /**
     * Method for running nested loads
     *
     * @param  parent:String
     *
     */
    getNestedLoadByParent: function (parent) {
        if (typeof (this.nestedLoads[parent]) !== 'undefined') {
            return this.nestedLoads[parent];
        }
        return null;
    },

    setActiveLoad: function (uuid, loadName) {
        this._activeLoads[uuid] = loadName;
    },

    getActiveLoadByUUID: function (uuid) {
        if (this._activeLoads[uuid]) {
            return this._activeLoads[uuid];
        }
        return null;
    },

    removeActiveLoad: function (uuid) {
        delete this._activeLoads[uuid];
    },


    /**
     * Method for sending single request
     *
     * @param {String} action
     * @param {Object} params
     * @param {Function} onComplete
     * @param {Function} onError
     *
     */
    action: function (action, params, onComplete, onError) {
        this.getNGSItemObjectByNameAndType(action, 'action').then(function (actionObject) {
            let _actionCallbackFunction = function () {
                onCompleteCallback.forEach(calback => {
                    calback.apply(this, arguments);
                });
            };

            let _actionErrorCallbackFunction = function () {
                for (let i = 0; i < onErrorCallback.length; i++) {
                    let result = onErrorCallback[i].apply(this, arguments);
                    if (result === false) {
                        break;
                    }
                }
            };
            let onCompleteCallback = [actionObject.onComplate];
            if (typeof (onComplete) === 'function') {
                onCompleteCallback.push(onComplete);
            }
            let onErrorCallback = [];
            if (typeof (onError) === 'function') {
                onErrorCallback.push(onError);
                actionObject.onNoAccess = onError;
                actionObject.onInvalidUser = onError;
            }
            onErrorCallback.push(actionObject.onError);
            actionObject.onComplate = _actionCallbackFunction;
            actionObject.onError = _actionErrorCallbackFunction;
            actionObject.action(params);
        }).catch(function (e) {
            throw e;
        });
    },
    /**
     *
     * @param {string} action
     * @param {object} params
     * @param {BaseModel} model
     * @param {object} options
     * @return {Promise<AbstractAction>}
     */
    async call(action, params = {}, model = null, options = {}) {
        const ngsItemObject = new AbstractAction();

        if (options.paramsIn) {
            ngsItemObject.getParamsIn = () => {
                return options.paramsIn;
            };
        }
        if (options.method) {
            ngsItemObject.getMethod = () => {
                return options.method;
            };
        }
        if (options.method) {
            ngsItemObject.getMethod = () => {
                return options.method;
            };
        }
        let ngsItemModuleAndName = this.getNGSItemPackageAndName(action);
        ngsItemObject.setPackage(ngsItemModuleAndName['package']);
        ngsItemObject.setName(ngsItemModuleAndName['action']);
        let ngsModule = NGS.getModule();
        if (ngsItemModuleAndName['module']) {
            ngsModule = ngsItemModuleAndName['module'];
        }
        ngsItemObject.setNgsModule(ngsModule);
        ngsItemObject.setAction(action);
        ngsItemObject.onError = (response) => {
            const error = new Error(response.message);
            error.message = response.msg;
            error.code = response.code;
            throw error;
        }
        ngsItemObject.onNoAccess = (response) => {
            const error = new Error(response.message);
            error.message = response.msg;
            error.code = response.code;
            throw error;
        }
        ngsItemObject.onInvalidUser = (response) => {
            const error = new Error(response.message);
            error.message = response.msg;
            error.code = response.code;
            throw error;

        }
        const jsonResponse = await Dispatcher.call(ngsItemObject, params, ngsItemModuleAndName['type']);
        if (model && jsonResponse) {
            if (typeof model === 'object') {
                model.updateData(jsonResponse);
                return model;
            }
            return new model(jsonResponse);
        }
        return jsonResponse;
    },


    /**
     * Method for getting NGS item container Object
     *
     * @param  type:String
     *
     * return Object container
     */
    getContainerByType: function (type) {
        switch (type) {
            case 'action':
                return this.actionsContainer;
            case 'load':
                return this.loadsContainer;
            default:
                throw new Error('type of container not found');
        }
    },


    /**
     * Method for getting NGS item object
     *
     * @param  itemName:String
     * @param  type:String
     *
     * return Object loadObject
     */
    async getNGSItemObjectByNameAndType(itemName, type) {
        if (typeof (itemName) !== "string") {
            throw new Error(itemName + " " + type + " not found");
        }
        const action = itemName;
        const ngsItemPath = this.getModuleImportPath(itemName, type);
        const load = await import(ngsItemPath);
        let ngsItemObject = null;
        if (type === 'load') {
            let customElementName = itemName.replaceAll('.', '-').toLowerCase();
            if (!customElements.get(customElementName)) {
                customElements.define(customElementName, load.default);
            }
            ngsItemObject = document.createElement(customElementName);
        } else {
            ngsItemObject = new load.default();
        }
        let ngsItemModuleAndName = this.getNGSItemPackageAndName(itemName);
        ngsItemObject.setPackage(ngsItemModuleAndName['package']);
        ngsItemObject.setName(ngsItemModuleAndName['action']);
        ngsItemObject.setNgsModule(ngsItemModuleAndName['module']);
        ngsItemObject.setAction(action);
        return ngsItemObject;
    },

    async defineLoadElement(itemName) {
        const ngsItemPath = this.getModuleImportPath(itemName, 'load');
        const load = await import(ngsItemPath);
        let ngsItemObject = null;
        let customElementName = itemName.replaceAll('.', '-').toLowerCase();
        if (!customElements.get(customElementName)) {
            customElements.define(customElementName, load.default);
        }
    },

    getModuleImportPath(itemName, type) {
        let ngsItemType = type.charAt(0).toUpperCase() + type.slice(1);
        let ngsItemPackage = itemName.substring(0, itemName.lastIndexOf("."));

        if (this.getModule().toLowerCase() === ngsItemPackage) {
            ngsItemPackage = this.getModule();
        }
        if (ngsItemPackage.toLowerCase().indexOf(this.getModule().toLowerCase()) === 0) {
            ngsItemPackage = ngsItemPackage.substring(ngsItemPackage.indexOf(".") + 1);

        }
        let ngsItemModule = ngsItemPackage.replace(/\./g, '/', function (delim) {
            return delim.replace('_', '/');
        });

        let ngsItemName = itemName.substring(itemName.lastIndexOf(".") + 1);
        ngsItemName = ngsItemName.replace(/_(\w)/g, function (delim) {
            delim = delim.replace('_', '');
            return delim.charAt(0).toUpperCase() + delim.slice(1);
        });
        ngsItemName = ngsItemName.charAt(0).toUpperCase() + ngsItemName.slice(1);
        return '/' + this.getJsPublicDir() + '/' + ngsItemModule + '/' + ngsItemName + ngsItemType + '.js';
    },

    /**
     * Method for getting NGS item and package from itemName

     * @param {String} actionName
     *
     * return Object loadObject
     */
    getNGSItemPackageAndName: function (actionName) {
        let matches = actionName.match(/[a-zA-Z0-9\_\-]+/g);
        let module = matches[0];
        if (matches[0] === 'ngs' && matches[1] === 'cms') {
            matches = matches.slice(1);
            module = 'ngs-cms';
        }
        if (matches[0] === 'ngs' && matches[1] === 'AdminTools') {
            matches = matches.slice(1);
            module = 'ngs-AdminTools';
        }
        let action = matches[matches.length - 1];
        const myRegExp = new RegExp('([A-Z])', 'g');
        action = action.replace(myRegExp, "_$1").toLowerCase().replace(new RegExp('^_'), "");
        let packges = matches.slice(2, matches.length - 1);
        let _package = "";
        if (packges.length > 0) {
            let deilm = "";
            for (let i = 0; i < packges.length; i++) {
                _package += deilm + packges[i];
                deilm = ".";
            }
        }
        return {
            "type": matches[1],
            "module": module,
            "package": _package,
            "action": action
        };
    },


    /**
     * global function for setting Initial load
     *
     * @param  loadName:String
     * @param  params:Object
     *
     */

    setInitialLoad: function (loadName, params) {
        this.initialLoads["load"] = loadName;
        if (params) {
            this.initialLoads["params"] = params;
        }
    },

    /**
     * Initial load getter function
     *
     * @return  namespace:String
     *
     */
    getInitialLoad: function () {
        return this.initialLoads;
    },

    /**
     * module setter function
     *
     * @param  module:String
     *
     */
    setModule: function (module) {
        this.module = module;
    },

    defaultHeaders: {},
    setHttpDefaultHeaders: function (key, value) {
        NGS.defaultHeaders[key] = value;
    },
    getHttpDefaultHeaders: function () {
        return NGS.defaultHeaders;
    },

    /**
     * module getter function
     *
     * @return  module:String
     *
     */
    getModule: function () {
        return this.module;
    },

    setTmst: function (tmst) {
        this.tmstDiff = new Date().getTime() - tmst;
    },

    getTmst: function () {
        return new Date().getTime() - this.tmstDiff;
    },

    get global() {
        if (this._globals) {
            return this._globals;
        }
        this._globals = reactive({});
        return this._globals;
    },


    addGlobal(name, value) {
        this.global[name] = value;
    },

    ErrorException: function (message) {
        this.message = message;
        this.name = "UserException";
    },

    /**
     * Hellper method for extend one object from other
     *
     * @param  obj:Object
     * @param  inheritObject:Object
     *
     */
    extend: function (destination, source) {
        for (var property in source) {
            destination[property] = source[property];
        }
        return destination;
    },
    /**
     * Hellper method for getting empty function
     *
     */
    emptyFunction: function () {
        return function () {
        };
    },
    /**
     * Hellper method for geglobal scope eval
     * We use an anonymous function so that context is window
     *
     */
    globalEval: function (data) {
        var rnotwhite = /\S/;
        if (data && rnotwhite.test(data)) {
            (window.execScript ||
                function (data) {
                    window["eval"].call(window, data);
                })(data);
        }
    },
    eval: function (data) {
        window["eval"].call(NGS, data);
    },
    setModuleHttpHost: function (host) {
        this._moduleHost = host;
    },
    getModuleHttpHost: function (withPath, withProtacol) {
        return this._moduleHost;
    },

    setHttpHost: function (host) {
        this._host = host;
    },
    getHttpHost: function (withPath, withProtacol) {
        return this._host;
    },
    setStaticPath: function (staticPath) {
        this._staticPath = staticPath;
    },
    getStaticPath: function (withPath, withProtacol) {
        return this._staticPath;
    },
    setJsPublicDir: function (publicDir) {
        this._jsPublicDir = publicDir;
    },
    getJsPublicDir: function () {
        return this._jsPublicDir;
    },

    setComponentTampltePath: function (componentTampltePath) {
        this._componentTampltePath = componentTampltePath;
    },
    getComponentTampltePath: function () {
        return this._componentTampltePath;
    },

    showAjaxLoader: function () {
        if (NGS.getConfig().ajaxLoader) {
            var loader = document.getElementById(NGS.getConfig().ajaxLoader);
            if (loader) {
                loader.style.display = "block";
            }

        }
    },

    hideAjaxLoader: function () {
        if (NGS.getConfig().ajaxLoader) {
            var loader = document.getElementById(NGS.getConfig().ajaxLoader);
            if (loader) {
                loader.style.display = "none";
            }
        }
    },

    onAjaxProgress: function () {

    },

    guid: function () {
        function s4() {
            return Math.floor((1 + Math.random()) * 0x10000)
                .toString(16)
                .substring(1);
        }

        return s4() + s4() + '-' + s4() + '-' + s4() + '-' +
            s4() + '-' + s4() + s4() + s4();
    },
    sessionId: function () {
        if (this._sessionId) {
            return this._sessionId;
        }
        this._sessionId = this.guid();
        return this._sessionId;
    },
    toNode: function (str) {
        let template = document.createElement("template");
        template.innerHTML = str;
        let nodelist = template.content;
        if (!nodelist.children) {
            return null;
        }
        if (nodelist.children.length === 1) {
            return nodelist.children[0];
        }
        return nodelist.children;
    },
    _isInitedUtilities: false,
    _initUtilities: function () {
        if (this._isInitedUtilities) {
            return;
        }

        //utilities
        if (typeof window !== 'undefined') {
            window.$$ = function (htmlStr) {
                return NGS.toNode(htmlStr);
            };

//hide element
            Node.prototype.hide = function () {
                this.style.display = 'none';
            };
            NodeList.prototype.hide = function () {
                if (this.length < 0) {
                    return false;
                }
                this.forEach((elem) => {
                    elem.hide();
                });
                return true;
            };
//hide element
            Node.prototype.show = function (type = 'block') {
                this.style.display = type;
            };
            NodeList.prototype.show = function (type = 'block') {
                if (this.length < 0) {
                    return false;
                }
                this.forEach((elem) => {
                    elem.show(type);
                });
                return true;
            };
//removeClass
            Node.prototype.removeClass = function (className) {
                className.split(" ").forEach((value) => {
                    this.classList.remove(value);
                });
            };
            NodeList.prototype.removeClass = function (className) {
                if (this.length < 0) {
                    return false;
                }
                this.forEach((elem) => {
                    elem.removeClass(className);
                });
                return true;
            };
//addClass
            Node.prototype.addClass = function (className) {
                className.split(" ").forEach((value) => {
                    this.classList.add(value);
                });
            };
            NodeList.prototype.addClass = function (className) {
                if (this.length < 0) {
                    return false;
                }
                this.forEach((elem) => {
                    elem.addClass(className);
                });
                return true;
            };
            Node.prototype.hasClass = function (className) {
                return this.classList.contains(className);
            };
//add event listener
            NodeList.prototype.on = function (type, listner, options) {
                if (this.length < 0) {
                    return false;
                }
                this.forEach((elem) => {
                    elem.addEventListener(type, listner, options);
                });
                return true;
            };
//remove event listener
            NodeList.prototype.off = function (type, listner, options) {
                if (this.length < 0) {
                    return false;
                }
                this.forEach((elem) => {
                    elem.removeEventListener(type, listner, options);
                });
                return true;
            };
            let setElemAttribute = function (elem, name, value) {
                if (elem instanceof Node) {
                    return elem.setAttribute(name, value);
                }
                return false;
            };
            let getElemAttribute = function (elem, name) {
                if (elem instanceof Node) {
                    return elem.getAttribute(name);
                }
                return false;
            };
            //add Attribute
            Node.prototype.attr = function (name, value) {
                if (value) {
                    return setElemAttribute(this, name, value);
                }
                return getElemAttribute(this, name);
            };
            NodeList.prototype.attr = function (atribute, value) {
                if (this.length < 0) {
                    return false;
                }
                let statusArr = [];
                this.forEach((elem) => {
                    statusArr.push(elem.attr(atribute, value));
                });
                return statusArr;
            };

            NodeList.prototype.clickListeners = [];
            //click event listener
            NodeList.prototype.click = function (listner, options) {
                if (this.length < 0) {
                    return false;
                }
                let statusArr = [];
                this.forEach((elem) => {
                    statusArr.push(elem.addEventListener('click', listner, options));
                    NodeList.prototype.clickListeners.push({element: elem, listener: listner});
                });
                return statusArr;
            };

            NodeList.prototype.dblclick = function (listner, options) {
                if (this.length < 0) {
                    return false;
                }
                let statusArr = [];
                this.forEach((elem) => {
                    statusArr.push(elem.addEventListener('dblclick', listner, options));
                    NodeList.prototype.clickListeners.push({element: elem, listener: listner});
                });
                return statusArr;
            };


            NodeList.prototype.change = function (listener, options) {
                if (this.length < 0) {
                    return false;
                }
                let statusArr = [];
                this.forEach((elem) => {
                    statusArr.push(elem.addEventListener('change', listener, options));
                });
                return statusArr;
            };

            NodeList.prototype.keyup = function (listener, options) {
                if (this.length < 0) {
                    return false;
                }
                let statusArr = [];
                this.forEach((elem) => {
                    statusArr.push(elem.addEventListener('keyup', listener, options));
                });
                return statusArr;
            };

            NodeList.prototype.keydown = function (listener, options) {
                if (this.length < 0) {
                    return false;
                }
                let statusArr = [];
                this.forEach((elem) => {
                    statusArr.push(elem.addEventListener('keydown', listener, options));
                });
                return statusArr;
            };

            NodeList.prototype.input = function (listener, options) {
                if (this.length < 0) {
                    return false;
                }
                let statusArr = [];
                this.forEach((elem) => {
                    statusArr.push(elem.addEventListener('input', listener, options));
                });
                return statusArr;
            };

            Node.prototype.closest = function (className) {
                var node = this;

                while (!this.hasClass(className)) {
                    node = this.parentNode;
                    if (!node) {
                        return null
                    }
                }

                return node;
            };

            NodeList.prototype.unbindClick = function () {
                if (this.length < 0) {
                    return false;
                }
                let leftHandlers = [];
                this.forEach((elem) => {
                    for (let i = 0; i < NodeList.prototype.clickListeners.length; i++) {
                        if (NodeList.prototype.clickListeners[i].element !== elem) {
                            leftHandlers.push(NodeList.prototype.clickListeners[i]);
                        } else {
                            elem.removeEventListener('click', NodeList.prototype.clickListeners[i].listener);
                        }
                    }
                });
                NodeList.prototype.clickListeners = leftHandlers;
                return this;
            };
        }
        this._isInitedUtilities = true;
    }
};
if (typeof window !== 'undefined') {
    window.NGS = NGS;
}
NGS.initialize();
export default NGS;