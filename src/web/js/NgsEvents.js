let NgsEvents = {

  events: {
    onAfterLoad: "ngs-onAfterLoad",
    onBeforeLoad: "ngs-onBeforeLoad",
    onPageUpdate: "ngs-onPageUpdate",
    onUrlChange: "ngs-onUrlChange",
    onUrlUpdate: "ngs-onUrlUpdate",
    onNGSLoad: "onNGSLoad"
  },
  createEvent: function (eventName) {
    return new CustomEvent(eventName, {
      view: NGS,
      cancelable: false,
      detail: {}
    });
  },
  /**
   * private function fire event
   *
   * @param string event name
   *
   * @return call event callback function
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