;(function($){
  "use strict";

  window.llar = {
    progressbar: {
      timeouts: [],
      $bar: null,
      $fill: null,
      start: function() {

        if($('body').find('#llar-progress-bar').length) {

          this.$bar = $('body').find('#llar-progress-bar');

        } else {

          this.$bar = $('<div id="llar-progress-bar"><span></span></div>');

          $('body').prepend(this.$bar);
        }

        this.clearTimeouts();

        this.$fill = this.$bar.find('span');

        this.timeouts.push(setTimeout(function(){llar.progressbar.percent(35);}, 100));
        this.timeouts.push(setTimeout(function(){llar.progressbar.percent(60);}, 800));
        this.timeouts.push(setTimeout(function(){llar.progressbar.percent(75);}, 1400));
        this.timeouts.push(setTimeout(function(){llar.progressbar.percent(80);}, 1800));
        this.timeouts.push(setTimeout(function(){llar.progressbar.percent(85);}, 2200));
        this.timeouts.push(setTimeout(function(){llar.progressbar.percent(95);}, 2600));

      },
      percent: function(val) {
        this.$fill.css('width', val + '%');
      },
      clearTimeouts: function() {
        this.timeouts.forEach(function (t) {
          clearTimeout(t);
        });
      },
      stop: function() {

        this.clearTimeouts();

        this.percent(100);

        setTimeout(function () {
          llar.progressbar.$bar.remove();
        }, 500);
      }
    }
  };

  $(document).ready(function(){

  });
  
})(jQuery);