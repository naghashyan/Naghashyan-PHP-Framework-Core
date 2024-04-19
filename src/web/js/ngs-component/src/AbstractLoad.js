/**
 * @fileoverview
 * @class parrent class for all actions
 *
 * @author Levon Naghashyan
 * @site http://naghashyan.com
 * @mail levon@naghashyan.com
 * @year 2010-2023
 * @package ngs.framework
 * @version 3.0.0
 */
import AbstractRequest from './AbstractRequest.js';
import Dispatcher from './Dispatcher.js';
import NgsEvents from './NgsEvents.js';
import {createApp, reactive, toRaw} from './libs/vue.esm-browser.prod.min.js';
import AjaxLoader from "./AjaxLoader.js";
import UrlObserver from "./UrlObserver.js";

export default class AbstractLoad extends HTMLElement {

    #ngsContainerNode = null;
    #ngsInsertionMode = "override";

    #template = null;
    #templateContent = null;

    #cacheKey = 'NGS_CACHE';

    #loadData = null;

    #plainData = null;

    #isNestedLoad = false;

    #nestedLoadData = {};

    #vApp = null;
    #vMountedApp = null;

    #ns = reactive({});

    appModel = {};

    constructor() {
        super();
        const abstractRequest = new AbstractRequest();
        Object.getOwnPropertyNames(Object.getPrototypeOf(abstractRequest)).forEach(key => {
            if (!this[key])
                this[key] = abstractRequest[key];
        });
        Object.keys(abstractRequest).forEach(key => {
            if (!this[key])
                this[key] = abstractRequest[key];
        });
        let ngsModule = NGS.getModule();
        const ngsLoadFullName = this.localName.replaceAll('-', '.');
        const ngsItemModuleAndName = NGS.getNGSItemPackageAndName(ngsLoadFullName);
        this.setPackage(ngsItemModuleAndName['package']);
        this.setName(ngsItemModuleAndName['action']);
        if (this.getAttribute('data-ngs-module')) {
            ngsModule = this.getAttribute('data-ngs-module');
        }
        this.setNgsModule(ngsModule);
        this.ngsPermalink = null;
        this._parentLoadName = null;
        this._parentLoad = null;
        this._abort = false;
        this.loadIdentifier = this.localName.substring(this.localName.indexOf('loads-') + 6);
    }

    async connectedCallback() {
        await this.service();
        this.beforeLoad();
    }

    disconnectedCallback() {
        this.onUnLoad();
    }

    initializeLoad() {
    }

    /**
     * The main method, which invokes load operation, i.e. ajax call to the backend and then updates corresponding container with the response
     */
    async service() {
        const content = await this.getTemplateContent();
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = content;
        tempDiv.querySelectorAll('[data-ngs-component]').forEach((node) => {
            NGS.defineLoadElement(node.localName.replaceAll('-', '.'));
        });
        this.updateContent(tempDiv.innerHTML, this);
        //fire after load event
        NgsEvents.fireEvent('onAfterLoad', {"load": this});

        this.initializeLoad();
        this.initVueApp();
    }


    getToRaw(data) {
        return toRaw(data);
    }


    initVueApp() {
        const appMethods = this.appMethods();
        const appComputed = this.appComputed();
        const appComponents = this.appComponents();

        this.#vApp = createApp({
            data: () => {
                let appModel = null;
                const loadModel = this.getAppModel();
                if (loadModel) {
                    this.appModel = Object.assign(this.getDefaultAppModel(), loadModel);
                } else {
                    this.appModel = this.getDefaultAppModel();
                }
                this.setArgs(this.appModel);
                return this.appModel;
            },
            created: () => {
                this.appData().then((data) => {
                    let routes = this.getRoutes();
                    if (!data) {
                        this.setArgs(this.#vMountedApp.$data);
                        if (routes !== null) {
                            UrlObserver.onUrlUpdateHandle(routes);
                        }
                        this.afterLoad();
                        return;
                    }
                    const dataFunctions = Object.keys(data);
                    dataFunctions?.forEach(key => {
                        this.#vMountedApp.$data[key] = data[key];
                    });
                    Object.getOwnPropertyNames(Object.getPrototypeOf(data)).forEach(key => {
                        this.#vMountedApp[key] = data[key];
                    });
                    this.setArgs(this.#vMountedApp.$data);
                    routes = this.getRoutes();
                    if (routes !== null) {
                        UrlObserver.onUrlUpdateHandle(routes);
                    }
                    this.afterLoad();
                }).catch((e) => {
                    console.error(e);
                    return {};
                });
                return {};
            },
            computed: {
                albumName: function(){
                    return 'GAGOOOO';
                }
            },
            methods: appMethods,
            components: appComponents,
            delimiters: ['${', '}']
        });
        this.#vApp.config.globalProperties.NGS = NGS.global;
        this.#vApp.config.globalProperties.load = this;
        this.#vApp.config.globalProperties.ns = this.#ns;
        this.#vMountedApp = this.#vApp.mount(this);
    }

    getAppModel() {
        return {};
    }

    getDefaultAppModel() {
        return {};
    }

    ns() {
        return this.#ns;
    }

    async appData(reload = false) {
        let response = null;
        if (this.isNestedLoad) {
            if (typeof this.nestedLoadData === 'function') {
                return this.nestedLoadData;
            }
            response = this.nestedLoadData;
        } else {
            response = await Dispatcher.componentLoad(this, this.getParams());

        }
        this.#plainData = response;
        return this.fillModel(response);
    }

    async fillModel(response) {
        let appModel = this.getAppModel();
        if (!appModel) {
            return response;
        }
        appModel.updateData(response);
        this.#loadData = appModel;
        return this.#loadData;
    }

    async loadData() {

        const response = await Dispatcher.componentLoad(this, this.getParams());
        let appModel = this.getAppModel();
        if (!appModel) {
            return response;
        }
        return appModel.updateData(response);
    }

    appComputed() {
    }

    appMethods() {
        return {};
    }

    appComponents() {
        return {};
    }

    getVMountedApp() {
        return this.#vApp;
    }

    getVApp() {
        return this.#vMountedApp;
    }

    get plainData() {
        return this.#plainData
    }

    /**
     * The main method, which invokes load operation, i.e. ajax call to the backend and then updates corresponding container with the response
     *
     * @param {Node} parentElement
     * @param {Object} params  http parameters which will be sent to the serverside Load, these parameters will be added to the ajax loader's default parameters
     * @param {Boolean} updateContent

     */
    load(params, parentElement = null, updateContent = true) {

        if (!this.isNestedLoad) {
            this.setParams(params);
        }
        if (!updateContent) {
            return;
        }
        if (this.abort) {
            return false;
        }
        if (!parentElement) {
            parentElement = this.getContentElem();

        }
        this.updateContent(this, parentElement);
    }


    get isNestedLoad() {
        return this.#isNestedLoad;
    }

    set isNestedLoad(value) {
        this.#isNestedLoad = value;
    }

    get nestedLoadData() {
        return this.#nestedLoadData;
    }

    set nestedLoadData(value) {
        this.#nestedLoadData = value;
    }

    /**
     * The main method, which invokes load operation without ajax sending
     *
     * @param {Object} params  http parameters which will be sent to the serverside Load, these parameters will be added to the ajax loader's default parameters
     * @param {Node} parentElement
     * @param {Boolean} updateContent

     */
    nestLoad(params, parentElement = null, updateContent = true) {
        this.isNestedLoad = true;
        this.nestedLoadData = params;
        this.load(params, parentElement, updateContent);
    }


    getPageTitle() {
        return "";
    }

    /**
     * Abstract method for returning container of the load, Children of the AbstractLoad class should override this method
     *
     * @return  Node|null

     */
    getContainer() {
        return this.#ngsContainerNode;
    }

    /**
     * Abstract method for returning container of the load, Children of the AbstractLoad class should override this method
     *
     *  @param {Node} containerNode
     *
     * @return  void

     */
    setContainer(containerNode) {
        this.#ngsContainerNode = containerNode;
    }

    /**
     *
     * @return {string}
     */
    getInsertionMode() {
        return this.#ngsInsertionMode;
    }

    /**
     * Abstract method for returning container of the load, Children of the AbstractLoad class should override this method
     *
     *  @param {string} ngsInsertionMode
     *
     * @return  void

     */
    setInsertionMode(ngsInsertionMode) {
        this.#ngsInsertionMode = ngsInsertionMode;
    }

    /**
     * In case of the  framework uses own containers, for indicating the container of the main content,
     * without pagging panels
     * @return  string

     */
    getOwnContainer() {
        return "";
    }


    /**
     * Abstract function, Child classes should be override this function,
     * and should return the name of the server load, formated with framework's URL nameing convention
     * @return string name of the server load, formated with framework's URL nameing convention

     */
    getUrl() {
        return "";
    }

    /**
     * Method returns Load's http parameters
     *
     * @return  http parameters of the load

     */
    getUrlParams() {
        return null;
    }


    /**
     * Method is used for setting error indicator if it was sent from the server. Intended to be used internally
     *
     * @param  wasError boolean parameter, shows existence of the error

     */
    setError(wasError) {
        this.wasError = wasError;
    }


    /**
     * @typedef {Object} Routes
     * @property {Route} permalink - url path example: album/tracks
     * @property {String} load - should open when url with matched example: IM.loads.album.tracks
     * @property {(String|undefined)} container - dom element that will be used for added load content example: #content, .content
     * @property {(Object|undefined)} params - parameters that will be passed to load
     * @property {(Route[]|undefined)} nestLoads
     *
     */
    /**
     *
     * @returns {(Routes|null)}
     */
    getRoutes() {
        return null;
    }

    setParentLoadName(parent) {
        this._parentLoadName = parent;
    }

    getParentLoadName() {
        return this._parentLoadName;
    }

    setNGSParentLoad(load) {
        this._parentLoad = load;
    }

    getNGSParentLoad() {
        return this._parentLoad;
    }

    /**
     *
     * return container DOM element
     *
     * @returns {string|null|HTMLElement}
     * @private
     */
    getContentElem() {
        if (!this.getContainer()) {
            return null;
        }
        if (this.getContainer() instanceof Node) {
            return this.getContainer();
        }
        let containerElem = document.getElementById(this.getContainer());
        if (!containerElem) {
            try {
                containerElem = document.querySelector(this.getContainer());
            } catch (error) {
                return null;
            }
        }
        return containerElem;
    }

    getLoadUUID() {
        return this._ngsUUID;
    }

    updateContent(content, parentElement) {
        switch (this.getInsertionMode()) {
            case "override":
                if (!(content instanceof Node)) {
                    parentElement.innerHTML = content;
                    break;
                }
                if (parentElement.childNodes.length === 0) {
                    parentElement.appendChild(content);
                } else {
                    parentElement.replaceChild(content, parentElement.childNodes[0]);
                }
                break;
            case "beforebegin":
            case "afterbegin":
            case "beforeend":
            case "afterend":
                if (!(content instanceof Element)) {
                    parentElement.insertAdjacentHTML(this.getInsertionMode(), content);
                    break;
                }

                parentElement.insertAdjacentElement(this.getInsertionMode(), content);
                break;
            case "none":
                break;
        }
        NgsEvents.fireEvent('onPageUpdate');
    }


    /**
     * Function, which is called before ajax request of the load. Can be overridden by the children of the class
     *

     */
    beforeLoad() {
        NgsEvents.fireEvent('onBeforeLoad', {
            "load": this
        });
    }

    /**
     * Function, which is called after load is done. Can be overridden by the children of the class
     * @transport  Object of the HttpXmlRequest class

     */
    afterLoad(params) {

    }

    terminate() {

    }

    onUnLoad() {

    }

    pauseLoad() {
        this.abort = true;
    }

    getTemplate() {
        return this.#template;
    }

    setTemplate(template) {
        this.#template = template;
    }

    async getTemplateContent() {
        let templatePath = null;
        if (this.#templateContent) {
            return this.#templateContent;
        }
        if (!this.getTemplate()) {
            templatePath = NGS.getComponentTampltePath() + this.loadIdentifier.replaceAll('-', '/').replaceAll('_', '-') + '.html';
        } else {
            templatePath = NGS.getComponentTampltePath() + this.getTemplate() + '.html';
        }

        const NgsCache = await caches.open(this.#cacheKey);
        let response = await NgsCache.match(templatePath);
        if (response && NGS.getEnvironment() !== 'development') {
            //     this.#templateContent = await response.text();
            //    return this.#templateContent;
        }
        const request = await AjaxLoader.request(templatePath, {
            method: 'GET',
            mode: 'cors',
        });
        if (request.status >= 400) {
            throw new Error('template not found');
        }
        if (NGS.getEnvironment() === 'development') {
            this.#templateContent = await request.text();
            return this.#templateContent;
        }
        await NgsCache.put(templatePath, request);
        response = await NgsCache.match(templatePath);
        this.#templateContent = await response.text();
        return this.#templateContent;
    }

    setTemplateContent(content) {
        this.#templateContent = content;
    }

};