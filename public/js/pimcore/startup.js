pimcore.registerNS("pimcore.plugin.TorqPimcoreHelpersBundle");

pimcore.plugin.TorqPimcoreHelpersBundle = Class.create({

    initialize: function () {
        document.addEventListener(pimcore.events.pimcoreReady, this.pimcoreReady.bind(this));
    },

    pimcoreReady: function (e) {
        // alert("TorqPimcoreHelpersBundle ready!");
    }
});

var TorqPimcoreHelpersBundlePlugin = new pimcore.plugin.TorqPimcoreHelpersBundle();
