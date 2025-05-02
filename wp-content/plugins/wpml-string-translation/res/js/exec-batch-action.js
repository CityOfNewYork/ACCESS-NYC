jQuery(function () {
    WPML_String_Translation.ExecBatchAction.init(jQuery('#wpml-icl-string-translations-batch-loader'));
});

var WPML_String_Translation = WPML_String_Translation || {};

WPML_String_Translation.ExecBatchAction = {
    BATCH_SIZE: 50,

    isApplyBulkActionSelected() {
        var msg = jQuery('.js-wpml-st-table').find('.js-wpml-st-icl-string-translations-bulk-select-msg');
		return msg.get(0).hasAttribute('data-is-apply-bulk-action-selected');
    },

    init: function(loader) {
        this.loader = loader;
        this.totalItemsCount = 0;
    },

    run: function(initData, processBatchData, handlerData, options) {
        var self = this;
        options = options || {};
        if(typeof options.beforeStart === 'undefined') {
            options.beforeStart = function() {};
        }
        if(typeof options.onComplete === 'undefined') {
            options.onComplete = function() {};
        }
        this.loader.css('display', 'block');
        options.beforeStart();

        var data = {
            action: 'wpml_action',
            data: JSON.stringify(handlerData),
            endpoint: initData.endpoint,
            nonce: initData.nonce,
        };

        jQuery.ajax({
            url:      ajaxurl,
            type:     'POST',
            data:     data,
            dataType: 'json',
            success: function(res) {
                if(!res.success) {
                    window.alert('Error: ' + res.data);
                    return;
                }

                self.totalItemsCount = res.data.totalItemsCount;
                self.completedItemsCount = 0;
                self.runNextBatch(processBatchData, handlerData, options);
            }
        });
    },

    runNextBatch: function(processBatchData, handlerData, options) {
        handlerData.batchSize = WPML_String_Translation.ExecBatchAction.BATCH_SIZE;
        var self = this;
        var data = {
            action: 'wpml_action',
            data: JSON.stringify(handlerData),
            endpoint: processBatchData.endpoint,
            nonce: processBatchData.nonce,
        };

        jQuery.ajax({
            url:      ajaxurl,
            type:     'POST',
            data:     data,
            dataType: 'json',
            success: function(res) {
                if(!res.success) {
                    window.alert('Error: ' + res.data);
                    return;
                }

                self.completedItemsCount += parseInt(res.data.completedCount, 10);
                self.updatePercentage(Math.ceil(100 * (self.completedItemsCount / self.totalItemsCount)));
                if(self.totalItemsCount > self.completedItemsCount) {
                    self.runNextBatch(processBatchData, handlerData, options);
                } else {
                    self.updatePercentage(0);
                    self.loader.css('display', 'none');
                    options.onComplete(res.data);
                }
            }
        });
    },

    updatePercentage: function(pt) {
        this.loader.find('.js-content-percentage').text(pt + '%');
        this.loader.find('.js-content-percentage-bar-status').attr('style', 'width: ' + pt + '%');
    },
}