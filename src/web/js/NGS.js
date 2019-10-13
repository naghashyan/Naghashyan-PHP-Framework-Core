/**
 * Base ngs object
 * for static function that will
 * vissible from any classes
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2014-2015
 * @package ngs.framework
 * @version 2.1.1
 *
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *
 */
window.NGS = {
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
  tmstDiff: 0,
  inited: false,
  _host: "",
  _moduleHost: "",
  _path: "",
  _jsPublicDir: 'js',
  _activeLoads: {},
  config: {
    defaultIndicator: false,//element id(false|String)
    initialLoad: "",
    initialLoadParams: {},
    dynContainer: "dyn",
    ajaxLoader: false
  },

  /**
   * Method for creating load object
   *
   *
   */
  getConfig: function () {
    if(typeof (NGS.config) !== "undefined"){
      return NGS.config;
    }
    return {};
  },


  /**
   * Method for run selected load
   *
   * @param  loadName:String
   * @param  params:Object
   * @param  callback:function
   *
   */
  load: function (loadName, params, callback) {
    this.getNGSItemObjectByNameAndType(loadName, "load").then(function (laodObj) {
      this.nestedLoads = {};
      if(typeof (callback) === "function"){
        laodObj.onComplate = callback;
      }
      if(typeof (callback) === "object"){
        laodObj = Object.assign(laodObj, callback);
      }
      laodObj.load(params);
    }.bind(this)).catch(function (e) {
      throw e;
    });

  },

  /**
   * Method for running nested loads
   *
   * @param  loadName:String
   * @param  params:Object
   * @param  parent:String
   *
   */
  nestLoad: function (loadName, params, parent) {
    this.getNGSItemObjectByNameAndType(loadName, "load").then(function (laodObj) {
      laodObj.nestLoad(parent, params);
      this.nestLoads();
    }.bind(this)).catch(function (e) {
      throw e;
    });
  },

  /**
   * Method for running nested loads
   *
   */
  nestLoads: function () {
    try {
      for (let i = 0; i < this.nestedLoads.length; i++) {
        let nestLoad = this.nestedLoads[i];
        this.getNGSItemObjectByNameAndType("load", nestLoad["load"]).nestLoad(nestLoad["parent"], nestLoad["params"]);
      }
    } catch (e) {
      throw e;
    }
  },

  /**
   * Method for running nested loads
   *
   * @param  parent:String
   * @param  loadName:String
   * @param  params:Object
   *
   */
  setNestedLoad: function (parent, loadName, params) {
    if(typeof (this.nestedLoads[parent]) === "undefined"){
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
    if(typeof (this.nestedLoads[parent]) !== "undefined"){
      return this.nestedLoads[parent];
    }
    return null;
  },

  setActiveLoad: function (uuid, loadName) {
    this._activeLoads[uuid] = loadName;
  },

  getActiveLoadByUUID: function (uuid) {
    if(this._activeLoads[uuid]){
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
   * @param  string urlPath
   * @param  ibject params
   * @param  Function callBack
   *
   */
  action: function (action, params, onComplate, onError) {
    this.getNGSItemObjectByNameAndType(action, "action").then(function (actionObject) {
      if(typeof (onComplate) === "function"){
        actionObject.onComplate = onComplate;
      }
      if(typeof (onError) === "function"){
        actionObject.onError = onError;
        actionObject.onNoAccess = onError;
        actionObject.onInvalidUser = onError;
      }
      actionObject.action(params);
    }).catch(function (e) {
      throw e;
    });

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
      case "action":
        return this.actionsContainer;
        break;
      case "load":
        return this.loadsContainer;
        break;
      default:
        throw new Error("type of container not found");
    }
  },

  /**
   * Method for getting NGS default parent Objects
   *
   * @param  type:String
   *
   * return Object container
   */
  getDefaultNGSParentClassByType: function (type) {
    switch (type) {
      case "action":
        return NGS.AbstractAction;
        break;
      case "load":
        return NGS.AbstractLoad;
        break;
      default:
        throw new Error("type of container not found");
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
  getNGSItemObjectByNameAndType: function (itemName, type) {
    return new Promise(function (resolve, reject) {
      if(typeof (itemName) != "string"){
        reject(new Error(itemName + " " + type + " not found"));
      }
      let action = itemName;
      let ngsItemType = type.charAt(0).toUpperCase() + type.slice(1);
      let ngsItemPackage = itemName.substr(0, itemName.lastIndexOf("."));
      ngsItemPackage = ngsItemPackage.substr(ngsItemPackage.indexOf(".") + 1);
      let ngsItemModule = ngsItemPackage.replace(/\./g, '/', function (delim) {
        return delim.replace('_', '/');
      });
      let ngsItemName = itemName.substr(itemName.lastIndexOf(".") + 1);
      ngsItemName = ngsItemName.replace(/_(\w)/g, function (delim) {
        delim = delim.replace('_', '');
        return delim.charAt(0).toUpperCase() + delim.slice(1);
      });
      ngsItemName = ngsItemName.charAt(0).toUpperCase() + ngsItemName.slice(1);
      let ngsItemPath = '/' + this.getJsPublicDir() + '/' + ngsItemModule + '/' + ngsItemName + ngsItemType + '.js';
      import(ngsItemPath).then(function (load) {
        let ngsItemObject = new load.default();
        let ngsItemModuleAndName = this.getNGSItemPackageAndName(itemName);
        ngsItemObject.setPackage(ngsItemModuleAndName['package']);
        ngsItemObject.setName(ngsItemModuleAndName['action']);
        ngsItemObject.setAction(action);
        resolve(ngsItemObject);
      }.bind(this)).catch(function (e) {
        reject(e);
      });
    }.bind(this));
  },

  /**
   * Method for getting NGS item and package from itemName

   * @param  itemName:String
   *
   * return Object loadObject
   */
  getNGSItemPackageAndName: function (actionName) {
    var matches = actionName.match(/[a-zA-Z0-9\_\-]+/g);
    var action = matches[matches.length - 1];
    var myRegExp = new RegExp('([A-Z])', 'g');
    action = action.replace(myRegExp, "_$1").toLowerCase().replace(new RegExp('^_'), "");
    var packges = matches.slice(2, matches.length - 1);
    var _package = "";
    if(packges.length > 0){
      var deilm = "";
      for (var i = 0; i < packges.length; i++) {
        _package += deilm + packges[i];
        deilm = ".";
      }
    }
    return {
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
    if(params){
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
    if(data && rnotwhite.test(data)){
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

  showAjaxLoader: function () {
    if(NGS.getConfig().ajaxLoader){
      var loader = document.getElementById(NGS.getConfig().ajaxLoader);
      if(loader){
        loader.style.display = "block";
      }

    }
  },

  hideAjaxLoader: function () {
    if(NGS.getConfig().ajaxLoader){
      var loader = document.getElementById(NGS.getConfig().ajaxLoader);
      if(loader){
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
    if(this._sessionId){
      return this._sessionId;
    }
    this._sessionId = this.guid();
    return this._sessionId;
  }
};

