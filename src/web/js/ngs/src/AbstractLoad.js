/**
 * @fileoverview
 * @class parrent class for all actions
 *
 * @author Levon Naghashyan
 * @site http://naghashyan.com
 * @mail levon@naghashyan.com
 * @year 2010-2020
 * @package ngs.framwork
 * @version 3.0.0
 */
import AbstractRequest from './AbstractRequest.js';
import Dispatcher from './Dispatcher.js';
import NgsEvents from './NgsEvents.js';
import UrlObserver from './UrlObserver.js';

export default class AbstractLoad extends AbstractRequest {


  constructor() {
    super();
    this.ngsPermalink = null;
    this._parentLoadName = null;
    this._parentLoad = null;
    this._abort = false;
    this._ngsUUID = "";
  }

  /**
   * The main method, which invokes load operation, i.e. ajax call to the backend and then updates corresponding container with the response
   *
   * @param  params  http parameters which will be sent to the serverside Load, these parameters will be added to the ajax loader's default parameters

   */
  service(params) {

    let containerElem = this._getContentElem();
    if(containerElem){
      let parentElem = containerElem.parentElement.closest("[data-ngs-uuid]");
      if(parentElem){
        let parentLoad = NGS.getActiveLoadByUUID(parentElem.getAttribute("data-ngs-uuid"));
        if(parentLoad){
          this.setNGSParentLoad(parentLoad);
        }
      }
    }
    if(containerElem){
      this._ngsUUID = NGS.guid();
      containerElem.setAttribute("data-ngs-uuid", this._ngsUUID);
    }
    NGS.setActiveLoad(this._ngsUUID, this);


    if(this.getPermalink() != null){
      NgsEvents.fireEvent('onUrlUpdate', {"load": this});
    }
    //fire after load event
    NgsEvents.fireEvent('onAfterLoad', {"load": this});
    let onPageUpdateLoadHandler = function () {
      let containerElem = this._getContentElem();
      if(!containerElem){
        document.removeEventListener("ngs-onAfterLoad", onPageUpdateLoadHandler);
        this.onUnLoad({containerNotFound: true});
        this.terminate();
        return;
      }
      if(containerElem.getAttribute("data-ngs-uuid") !== this.getLoadUUID()){
        document.removeEventListener("ngs-onAfterLoad", onPageUpdateLoadHandler);
        NGS.removeActiveLoad(this._ngsUUID);
        this.terminate();
        this.onUnLoad();
      }
    }.bind(this);
    this.initializeLoad();
    this.afterLoad(params);
    document.addEventListener("ngs-onAfterLoad", onPageUpdateLoadHandler);
    let loadsArr = NGS.getNestedLoadByParent(this.getAction());
    if(loadsArr == null){
      return;
    }
    for (let i = 0; i < loadsArr.length; i++) {
      NGS.nestLoad(loadsArr[i].load, loadsArr[i].params, this.getAction());
    }
  }


  initializeLoad() {
  }

  /**
   * The main method, which invokes load operation, i.e. ajax call to the backend and then updates corresponding container with the response
   *
   * @param  params  http parameters which will be sent to the serverside Load, these parameters will be added to the ajax loader's default parameters
   * @param  replace indicates should container be replaced itself(true) with the load response or should be replaced container's content(false)

   */
  load(params, replace) {

    this.beforeLoad();
    this.setParams(params);
    if(this.abort){
      return false;
    }
    this.runLoad();
  }

  runLoad() {
    Dispatcher.load(this, this.getParams());
  }

  /**
   * The main method, which invokes load operation without ajax sending
   *
   * @param  parent  loadName that will calling
   * @param  params http parameters which will be sent to the serverside Load, these parameters will be added to the ajax loader's default parameters

   */
  nestLoad(parent, params) {
    this.setArgs(params);
    this.beforeLoad();
    this.setParentLoadName(parent);
    this.service(params);

  }


  getPageTitle() {
    return "";
  }

  /**
   * Abstract method for returning container of the load, Children of the AbstractLoad class should override this method
   *
   * @return  string

   */
  getContainer() {
    return "";
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


  setPermalink(permalink) {
    this.ngsPermalink = permalink;
  }

  getPermalink() {
    return this.ngsPermalink;
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
  _getContentElem() {
    if(!this.getContainer()){
      return null;
    }
    if(this.getContainer() instanceof Node){
      return this.getContainer();
    }
    let containerElem = document.getElementById(this.getContainer());
    if(!containerElem){
      try {
        containerElem = document.querySelector(this.getContainer());
      } catch (error) {
        return null;
      }
    }
    return containerElem;
  }

  getInsertionMode() {
    return "override";
  }

  _updateContent(html, params) {
    //NGS.unLoad(this.getContainer());
    let containerElem = this._getContentElem();
    this.onUpdateConent(containerElem, html, function () {
      this.service(params);
    }.bind(this));
    NgsEvents.fireEvent('onPageUpdate');
  }

  getLoadUUID() {
    return this._ngsUUID;
  }

  onUpdateConent(elem, content, callback) {

    switch (this.getInsertionMode()) {
      case "override":
        elem.innerHTML = content;
        break;
      case "beforebegin":
      case "afterbegin":
      case "beforeend":
      case "afterend":
        elem.insertAdjacentHTML(this.getInsertionMode(), content);
        break;
      case "none":
        break;
    }

    callback();
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

  /**
   *
   * @param template
   * @param data
   * @param toObject
   * @returns {HTMLCollection|null|Element|*}
   */

  renderTemplate(template, data, toObject = true) {
    let htmlStr = template.replace(
      /\$\{\s*([^\s\}]+)\s*\}/g,
      (_, capturedIdentifier) =>
        data[capturedIdentifier]
    );
    if(toObject){
      return NGS.toNode(htmlStr);
    }
    return htmlStr;
  }
};
