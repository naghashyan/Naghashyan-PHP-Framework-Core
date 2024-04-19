/**
 * this function handle framework
 * load url changes using history object
 *
 * @author Levon Naghashyan
 * @site http://naghashyan.com
 * @mail levon@naghashyan.com
 * @year 2015
 * @version 2.0.0
 */
import NgsEvents from './NgsEvents.js';

let UrlObserver = {
  load: null,
  getCurrentState: function () {
    if(this.load == null){
      return null;
    }
    return this.load.getAction();
  },
  getCurrentStateParams: function () {
    if(this.load == null){
      return null;
    }
    return this.load.getParams();
  },
  onUrlUpdateHandle: function (e) {
    if(e.detail.load.getMethod().toLowerCase() !== "get"){
      return;
    }
    this.load = e.detail.load;
    let params = {};
    params["load"] = this.load.getAction();
    params["container"] = this.load.getContainer();
    params["params"] = this.load.getParams();
    if(this.load.getNGSParentLoad()){
      params["parent"] = {
        load: this.load.getNGSParentLoad().getAction(),
        params: this.load.getNGSParentLoad().getParams(),
        container: this.load.getNGSParentLoad().getContainer()
      };
    }

    let permalink = "";
    if(this.load.getPermalink() != null && this.load.getPermalink() !== ''){
      permalink = this.load.getPermalink();
      if(permalink.indexOf("/") !== 0){
        permalink = "/" + permalink;
      }
    }



    if(permalink === ""){
      return;
    }
    let uniq = 'id' + (new Date()).getTime();
    if(permalink === window.location.pathname){
      history.replaceState(params, uniq, permalink);
      return;
    }
    history.pushState(params, uniq, permalink);
  }
};
document.addEventListener("ngs-onUrlUpdate", UrlObserver.onUrlUpdateHandle.bind(UrlObserver));
window.onpopstate = function (e) {
  NgsEvents.fireEvent("onUrlChange", {
    "load": e
  });

  if(typeof e.state === "object" && e.state != null){
    if(typeof e.state.load === "string"){
      if(document.querySelector("#" + e.state.container)){
        NGS.load(e.state.load, e.state.params);
        return;
      }
      if(e.state.parent){
        document.querySelector("#" + e.state.parent.container).setAttribute("style", "visibility:hidden");
        NGS.load(e.state.parent.load, e.state.parent.params, function () {
          NGS.load(e.state.load, e.state.params, function () {
            document.querySelector("#" + e.state.parent.container).removeAttribute("style");
          });
        });
      }
    }
  }
};
export default UrlObserver;