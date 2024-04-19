/**
 * this function handle framework
 * load url changes using history object
 *
 * @author Levon Naghashyan
 * @site https://naghashyan.com
 * @mail levon@naghashyan.com
 * @year 2015-2023
 * @version 5.0.0
 */
import NgsEvents from './NgsEvents.js';

let UrlObserver = {
    routes: null,
    getCurrentStateParams: function () {
        return this.routes;
    },
    /**
     * @param routes
     */
    onUrlUpdateHandle: function (routes) {
        this.routes = routes;
        let params = {
            permalink: routes.permalink,
            load: routes.load,
        };
        if (routes.params) {
            params.params = routes.params;
        }
        if (routes.container) {
            params.container = routes.container;
        }
        if (routes.nestLoads) {
            params.nestLoads = routes.nestLoads;
        }
        let permalink = routes.permalink;
        if (permalink.indexOf("/") !== 0) {
            permalink = "/" + permalink;
        }
        if (permalink === '') {
            return;
        }
        let uniq = 'id' + (new Date()).getTime();
        if (permalink === window.location.pathname) {
            history.replaceState(params, uniq, permalink);
            return;
        }
        history.pushState(params, uniq, permalink);
        NgsEvents.fireEvent('onUrlUpdate', params);
    },
    goBack() {
        history.back();
    },
    /**
     * @param {Routes} loadParams
     */
    popState(loadParams) {
        if (loadParams.container) {
            if (document.querySelector(loadParams.container)) {
                document.querySelector(loadParams.container).load(loadParams.load, loadParams.params);
                this.nest(loadParams);
                return;
            }
        }
        NGS.load(loadParams.load, loadParams.params);
        this.nest(loadParams.nestLoads);
    },
    /**
     * @param {Routes[]} loadParams
     */
    nest(loadParams) {
        if (!loadParams.nestLoads) {
            return;
        }
        loadParams.forEach((loadParam) => {
            if (loadParam.container) {
                if (document.querySelector(loadParam.container)) {
                    document.querySelector(loadParam.container).nestLoad(loadParams.load, loadParams.params);
                    return;
                }
            }
            NGS.nestLoad(loadParam.load, loadParam.params);
        });
    }

};
window.onpopstate = function (e) {
    NgsEvents.fireEvent("onUrlChange", {
        "load": e
    });
    if (typeof e.state !== "object" || e.state === null) {
        return;
    }
    /**
     * @type {Routes}
     */
    let loadParams = e.state;
    UrlObserver.popState(loadParams);
};
export default UrlObserver;