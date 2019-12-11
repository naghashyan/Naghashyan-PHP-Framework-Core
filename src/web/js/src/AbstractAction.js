/**
 * @fileoverview
 * @class parrent class for all actions
 *
 * @author Levon Naghashyan
 * @site http://naghashyan.com
 * @mail levon@naghashyan.com
 * @year 2010-2019
 * @package ngs.framwork
 * @version 3.0.0
 */
import AbstractRequest from './AbstractRequest.js';
import Dispatcher from './Dispatcher.js';

export default class AbstractAction extends AbstractRequest {

  constructor() {
    super();
  }

  /**
   * The main method, which invokes action operation, i.e ajax call to the backend
   *
   */
  action(params) {
    this.beforeAction();
    this.setParams(params);
    Dispatcher.action(this, params);
  }


  /**
   * Function, which is called before ajax request of the action. Can be overridden by the children of the class
   *
   */
  beforeAction() {

  }

  /**
   * Function, which is called after action is done. Can be overridden by the children of the class
   * @param transport  Object of the HttpXmlRequest class
   */
  afterAction(params) {

  }


  /**
   * Corresponds to the serverside Action's redirectToLoad function, i.e if action returns some load content
   * corresponding load's html container will updated with it and load's afterLoad method will be called.
   * @param loadObj  Object of the load, to which action will be redirected
   * @param responseText  response content which is returned by the server side load, to which action was
   * redirected
   */
  redirectToLoad(loadObj, responseText, redirectOnError) {
    if(this.wasError && !redirectOnError){
      return;
    }
    let container = loadObj.getComputedContainer(false);
    let content = responseText;
    Element.update(container, content);
    if(this.params){
      loadObj.setParams(this.params);
    }
    this.ajaxLoader.afterLoad(loadObj, true);
  }
}