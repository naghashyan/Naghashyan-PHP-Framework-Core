/**
 *
 * this helper Object handle
 * all ajax request
 *
 * @author Levon Naghashyan
 * @site http://naghashyan.com
 * @mail levon@naghashyan.com
 * @year 2010-2018
 * @package ngs.framework
 * @version 2.0.0
 */
import './NGS.js';

let AjaxLoader = {

  /**
   * Method for require js file
   *
   * @param  _fileUrl:String
   * @param  callback: function
   *
   * return load script and do global eval
   */
  require: function (_fileUrl, callback) {
    this.request(_fileUrl, {
      headers: {
        contentType: "text/javascript, application/javascript, application/ecmascript, application/x-ecmascript, */*; q=0.01"
      },
      onComplete: function (data) {
        NGS.globalEval(data);
        callback();
      }
    });
  },

  /**
   * Method for ajax request handler
   *
   * @param  _url:String
   * @param  options:Object
   *
   */

  request: function (_url, options) {
    if(!options.withoutLoader){
      NGS.showAjaxLoader();
    }
    let defaultOptions = {
      method: "get",
      timeout: 60000,
      async: true,
      paramsIn: "query",
      params: {},
      headers: {
        ...NGS.getHttpDefaultHeaders(),
        accept: null,
        contentType: "application/x-www-form-urlencoded"
      },
      crossDomain: false,
      onCreate: NGS.emptyFunction,
      onComplete: NGS.emptyFunction,
      on403: NGS.emptyFunction,
      onError: NGS.emptyFunction,
      oneHttpError: NGS.emptyFunction,
      onProgress: NGS.emptyFunction,
      onInvalidUser: NGS.emptyFunction,
      onNoAccess: NGS.emptyFunction,
      onRedirect: NGS.emptyFunction,
      onXHRError: NGS.emptyFunction
    };
    options = NGS.extend(defaultOptions, options);
    let xmlhttp = new XMLHttpRequest();
    if(!(!!window.MSInputMethodContext && !!document.documentMode)){
      xmlhttp.timeout = options.timeout;
    }
    xmlhttp.onreadystatechange = function () {
      if(xmlhttp.readyState === 1){
        options.onCreate();
      }
      if(xmlhttp.readyState === 4){
        if(xmlhttp.status === 200){
          options.onComplete(xmlhttp.responseText);
        } else if(xmlhttp.status === 400){
          options.onError(xmlhttp.responseText);
        } else if(xmlhttp.status === 401){
          options.onInvalidUser(xmlhttp.responseText);
        } else if(xmlhttp.status === 403){
          options.onNoAccess(xmlhttp.responseText);
        } else if(xmlhttp.status === 301){
          options.onRedirect(xmlhttp.responseText);
          return;
        }
        if(!options.withoutLoader){
          NGS.hideAjaxLoader();
        }
      }
    }.bind(this);
    xmlhttp.onerror = options.onXHRError;
    xmlhttp.upload.addEventListener("progress", function (evt) {
      if(evt.lengthComputable){
        options.onProgress(Math.round(evt.loaded / evt.total * 100));
      }
    });

    let sendingData = null;
    let urlParams = "";
    if(options.method.toUpperCase() === 'DELETE' || options.method.toUpperCase() === 'GET' || options.paramsIn === "query" || options.method.toUpperCase() === 'DOWNLOAD'){
      urlParams = this.serializeUrl(options.params);
      if(urlParams){
        _url = _url + "?" + urlParams;
      }
      if(options.method.toUpperCase() === 'DOWNLOAD'){
        NGS.hideAjaxLoader();
        window.location = _url;
        return;
      }
    } else if(options.paramsIn === "formData"){
      if(options.params instanceof FormData){
        sendingData = options.params;
      } else{
        sendingData = new FormData();
        for (var i in options.params) {
          if(options.params.hasOwnProperty(i)){
            sendingData.set(i, options.params[i]);
          }
        }
      }

      options.headers.contentType = false;
    } else if(options.paramsIn === "body"){
      sendingData = JSON.stringify(options.params);
    }

    xmlhttp.open(options.method.toUpperCase(), _url, options.async);
    let headersArr = options.headers;
    for (let key in headersArr) {
      if(!headersArr.hasOwnProperty(key)){
        continue;
      }
      let header = headersArr[key];
      xmlhttp.setRequestHeader(key, header);
    }
    xmlhttp.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    if(options.crossDomain === true || NGS.getConfig().crossDomain === true){
      xmlhttp.setRequestHeader("Accept", "*");
    } else{
      xmlhttp.withCredentials = true;
    }
    if(options.headers.contentType){
      xmlhttp.setRequestHeader("Content-type", options.headers.contentType);
    }
    xmlhttp.send(sendingData);
  },

  /**
   * serialize obejct to url string
   *
   * @param  a:Object
   *
   **/
  serializeUrl: function (a) {
    var prefix, s, add, name, r20, output;
    s = [];
    r20 = /%20/g;
    add = function (key, value) {
      // If value is a function, invoke it and return its value
      value = (typeof value == 'function') ? value() : (value == null ? "" : value);
      s[s.length] = encodeURIComponent(key) + "=" + encodeURIComponent(value);
    };
    if(a instanceof Array){
      for (name in a) {
        add(name, a[name]);
      }
    } else{
      for (prefix in a) {
        this.buildParams(prefix, a[prefix], add);
      }
    }
    output = s.join("&").replace(r20, "+");
    return output;
  },
  buildParams: function (prefix, obj, add) {
    let name, i, l, rbracket;
    rbracket = /\[\]$/;
    if(obj instanceof Array){
      for (i = 0, l = obj.length; i < l; i++) {
        if(rbracket.test(prefix)){
          add(prefix, obj[i]);
        } else{
          this.buildParams(prefix + "[" + (typeof obj[i] === "object" ? i : "") + "]", obj[i], add);
        }
      }
    } else if(typeof obj == "object"){
      // Serialize object item.
      for (name in obj) {
        this.buildParams(prefix + "[" + name + "]", obj[name], add);
      }
    } else{
      // Serialize scalar item.
      add(prefix, obj);
    }
  },
  imageRequest: function (url, options) {
    var defaultOptions = {
      async: true,
      onComplete: NGS.emptyFunction,
      onError: NGS.emptyFunction
    };
    options = NGS.extend(defaultOptions, options);
    var img;
    if(/MSIE (\d+\.\d+);/.test(navigator.userAgent)){
      img = new Image();
    } else{
      img = document.createElement('img');
    }
    img.src = url;
    img.width = 0;
    img.async = true;
    img.height = 0;
    img.className = "im-pixel";
    img.onload = function () {
      document.body.removeChild(img);
      options.onComplete();
    };
    img.onerror = function () {
      document.body.removeChild(img);
      options.onError();
    };
    document.body.appendChild(img);
  }
};
export default AjaxLoader;