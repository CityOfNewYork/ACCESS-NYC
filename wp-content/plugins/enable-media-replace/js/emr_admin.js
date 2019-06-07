jQuery(document).ready(function($)
{
  // interface for emr.
  var emrIf = new function ()
  {

    this.init = function()
    {
      $('input[name="timestamp_replace"]').on('change', $.proxy(this.checkCustomDate, this));
      this.checkCustomDate();
      this.loadDatePicker();
    },
    this.loadDatePicker = function()
    {
      $('#emr_datepicker').datepicker({
        dateFormat: emr_options.dateFormat,
        onClose: function() {
              var date = $(this).datepicker( 'getDate' );
              if (date) {
                  var formattedDate = (date.getFullYear()) + "-" +
                            (date.getMonth()+1) + "-" +
                            date.getDate();
              $('input[name="custom_date_formatted"]').val(formattedDate);
              //$('input[name="custom_date"]').val($.datepicker.parseDate( emr_options.dateFormat, date));
              }
        },
      });
    },
    this.checkCustomDate = function()
    {
      console.log('check');
      if ($('input[name="timestamp_replace"]:checked').val() == 3)
        this.showCustomDate();
      else
        this.hideCustomDate();
    },
    this.showCustomDate = function()
    {
        $('.custom_date').css('visibility', 'visible').fadeTo(100, 1);
    },
    this.hideCustomDate = function()
    {
      $('.custom_date').fadeTo(100,0,
          function ()
          {
            $('.custom_date').css('visibility', 'hidden');
          });
    }
  } // emrIf

  /*emrIf.

  $('input[name="timestamp_replace"]').on('change',function(e)
  {
      var target = e.target;
      var value = $(e.target).val();
      if (value == 3) // custom date
      {
        $('.custom_date').css('visibility', 'visible').fadeTo(100, 1);
      }
      else {
        $('.custom_date').fadeTo(100,0,
            function ()
            {
              $('.custom_date').css('visibility', 'hidden');
            });
      }
  });*/

  window.enableMediaReplace = emrIf;
  window.enableMediaReplace.init();
});
