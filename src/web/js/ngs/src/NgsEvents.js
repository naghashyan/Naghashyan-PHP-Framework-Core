let NgsEvents = {

  events: {
    onAfterLoad: "ngs-onAfterLoad",
    onBeforeLoad: "ngs-onBeforeLoad",
    onPageUpdate: "ngs-onPageUpdate",
    onUrlChange: "ngs-onUrlChange",
    onUrlUpdate: "ngs-onUrlUpdate",
    onNGSLoad: "onNGSLoad",
    onNGSPageLoad: "onNGSPageLoad"
  }, createEvent: function (eventName) {
    return new CustomEvent(eventName, {
      view: NGS, cancelable: false, detail: {}
    });
  }, /**
   *
   * @param {string} eventName
   * @param {Function} handler
   * @param {Object}  options
   */
  subscribe(eventName, handler, options) {
    if(!this.events[eventName]){
      return false;
    }
    document.addEventListener(eventName, handler, options);
    return true;
  },

  /**
   *
   * @param {string} eventName
   * @param {Function} handler
   */
  unSubscribe(eventName, handler) {
    if(!this.events[eventName]){
      return false;
    }
    document.removeEventListener(eventName, handler);
    return true;
  }, /**
   * private function fire event
   *
   *
   * @return call event callback function
   * @param {string} eventName
   * @param {Object} params
   */
  fireEvent: function (eventName, params) {
    if(typeof params == "undefined"){
      params = {};
    }
    if(this.events[eventName]){
      let evt = this.createEvent(this.events[eventName]);
      for (let i in params) {
        evt.detail[i] = params[i];
      }
      document.dispatchEvent(evt);
    }
  }
};
export default NgsEvents;