/**
 * @author Levon Naghashyan
 * @site http://naghashyan.com
 * @mail levon@naghashyan.com
 * @year 2013-2019
 * @version 4.0.0
 */
import './NGS.js';
import AjaxLoader from './AjaxLoader.js';
import NgsEvents from './NgsEvents.js';

let Dispatcher = {

  loadsObject: {},

  initialize: function () {
    _initNgsDefaults();
    _ngs_defaults = [];
    if(NGS.getInitialLoad()){
      NGS.nestLoad(NGS.getInitialLoad().load, NGS.eval("(" + NGS.getInitialLoad().params + ")"));
    }
    NgsEvents.fireEvent('onNGSLoad');
  },

  load: function (loadObject, params) {
    var _url = "";
    if(loadObject.getUrl() !== ""){
      _url = this.computeUrl(loadObject.getUrl());
    } else{
      _url = this.computeUrl(loadObject.getPackage(), loadObject.getName());
    }
    var onComplete = function (responseText) {
      try {
        var res = JSON.parse(responseText);
        if(typeof (res) == "object" && typeof (res.nl)){
          for (var p in res.nl) {
            if(res.nl.hasOwnProperty(p)){
              var nestedLoad = res.nl[p];
              for (var i = 0; i < nestedLoad.length; i++) {
                NGS.setNestedLoad(p, nestedLoad[i].action, nestedLoad[i].params);
              }
            }
          }
        }
        loadObject.setArgs(res.params);
        loadObject.setPermalink(res.pl);
        loadObject._updateContent(res.html, res.params);
        loadObject.onComplate(res.params);
      } catch (e) {
        throw (e);
      }

    };
    this.doRequest(_url, loadObject, params, onComplete);
  },

  action: function (actionObject, params) {
    console.log(actionObject.getPackage(), "do_" + actionObject.getName());
    let _url = this.computeUrl(actionObject.getPackage(), "do_" + actionObject.getName());
    let onComplete = function (responseText) {
      var res = JSON.parse(responseText);
      actionObject.setArgs(res);
      actionObject.afterAction(res);
      actionObject.onComplate(res);
    };
    this.doRequest(_url, actionObject, params, onComplete);
  },


  doRequest: function (_url, requestObject, params, onComplete) {
    let options = {
      method: requestObject.getMethod(),
      paramsIn: requestObject.getParamsIn(),
      params: params,
      onProgress: requestObject.ngsOnProgress,
      onComplete: onComplete.bind(this),
      onXHRError: requestObject.onXHRError,
      onError: function (responseText) {
        var res = JSON.parse(responseText);
        requestObject.onError(res);
      }.bind(this),
      onInvalidUser: function (responseText) {
        var res = JSON.parse(responseText);
        requestObject.onInvalidUser(res);
      }.bind(this),
      onNoAccess: function (responseText) {
        var res = JSON.parse(responseText);
        requestObject.onNoAccess(res);
      }.bind(this)
    };
    AjaxLoader.request(_url, options);
  },

  apiCall: function (_url, params, onSucces) {
    _url = NGS.getConfig().apiUrl + "/" + _url.replace(".", "/");
    var options = {
      method: actionObject.getMethod(),
      params: params,
      onComplete: function (responseText) {
        var res = JSON.parse(responseText);
        onSucces(res);
      }.bind(this),
      onError: function (responseText) {
      }.bind(this)
    };
    AjaxLoader.request(_url, options);
  },

  /**
   * Method for computing request URLs depending on the current security level, baseUrl, package and command, mainly used internaly by the framework,
   *
   * @param  command  htto name of the load or action: SomeLoad: some, SomeAction: do_some
   * @return computedUrl computed URL of the request
   * @see
   */
  computeUrl: function () {
    let _package = arguments[0].replace(".", "_");
    let command = "";
    if(arguments.length === 2){
      command = arguments[1];
    }
    let dynContainer = "";
    if(NGS.getConfig().dynContainer !== ""){
      dynContainer = "/" + NGS.getConfig().dynContainer + "/";
    }
    let module = "";
    if(NGS.getModule() != null){
      module = NGS.getModule() + "/";
    }
    return NGS.getHttpHost() + dynContainer + _package + "/" + command;
  }
};
export default Dispatcher;