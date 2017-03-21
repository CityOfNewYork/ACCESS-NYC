/*better support for CORS on IE9 https://github.com/ebickle/snippets/blob/master/javascript/xdomainrequest/jquery.xdr-1.0.2.js*/

(function ($) {
    // The XDomainRequest objects will be stored in a global variable to prevent an
    // IE bug that causes the requests to be garbage collected while they're in progress.
    var xdrRequests = [];

    function corsSupported() {
        try {
            return typeof XMLHttpRequest !== "undefined" && ("withCredentials" in new XMLHttpRequest());
        }
        catch (e) {
            return false;
        }
    }

    if (typeof XDomainRequest !== "undefined" && !corsSupported()) {
        //console.log('transport added');
        $.oldiexdr = true;//added so that other libraries can know this support has been added, geez!
        $.ajaxTransport("+*", function (options, originalOptions, jqXHR) {
            var xdr, completed;

            function setCompletion() {
                completed = true;

                if (xdr) {
                    // Remove from global variable.
                    var arrayIndex = $.inArray(xdr, xdrRequests);
                    if (arrayIndex >= 0) {
                        xdrRequests.splice(arrayIndex, 1);
                    }

                    // Unhook event handlers.
                    xdr.onload = null;
                    xdr.onerror = null;
                    xdr.ontimeout = null;
                    xdr.onprogress = null;
                }
            }

            if (options.async && options.crossDomain) {

                return {

                    send: function (headers, complete) {
                        var timeout = options.xdrTimeout || 6e5; // 600 seconds by default.

                        xdr = new XDomainRequest();
                        xdr.timeout = timeout;
                        xdr.open(options.type, options.url);

                        // Listen to events.
                        // All of the events must be non-null or random failures will occur.
                        xdr.onload = function () {
                            ///console.log('success',xdr.responseText);
                            if (!completed) {
                                setCompletion();
                                complete(
                                    200,
                                    "OK",
                                    typeof xdr.responseText === "string" ? {
                                        text: xdr.responseText
                                    } : undefined,
                                    "Content-Type: " + xdr.contentType
                                );
                            }
                        };
                        xdr.onerror = function () {
                            //console.log('error',xdr.responseText);
                            if (!completed) {
                                setCompletion();
                                complete(500, "XDR Error");
                            }
                        };
                        xdr.ontimeout = function () {
                            if (!completed) {
                                setCompletion();
                                complete(500, "XDR Timeout");
                            }
                        };
                        xdr.onprogress = function () { };

                        // Send the request.
                        xdr.send(((options.hasContent && options.data) || null));

                        // Keep a reference to the XDomainRequest object around to avoid.
                        // IE8's buggy garbage collector from destroying it.
                        xdrRequests.push(xdr);
                    },

                    abort: function () {
                        if (xdr && !completed) {
                            setCompletion();
                            xdr.abort();
                        }
                    }
                };
            }
        });
    }
})(jQuery);
/*lets define our scope*/
(function($, wlocation, undefined){

   //let's make it easy to jQuery's form array into a data object
   $.fn.serializeObject = function(){
        var o = {},
            a = this.serializeArray();
        $.each(a, function() {
            if (o[this.name] !== undefined) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };

    var interactiveValidity = 'reportValidity' in $('<form/>').get()[0],//check whether the browser supports interactive validation messages
        pluginname = 'bsdSignup',//the plugin we plan to create
        gup = function(name) {
            var gupregex = new RegExp("[\\?&]"+name.replace(/(\[|\])/g,"\\$1")+"=([^&#]*)"),
                results = gupregex.exec( wlocation.href );
            return ( results === null )?"":results[1];
        },//allow us to get url parameters
        sourceString = 'source',
        subsourceString = 'subsource',
        urlsource = gup(sourceString)||gup('fb_ref'),//any source we can get from the url
        urlsubsource = gup(subsourceString);//any subsource we can get from the url

    function parseURL(url){
        var p = document.createElement('a');//create a special DOM node for testing
        p.href=url;//stick a link into it
        //p.pathname = p.pathname.replace(/(^\/?)/,"/");//IE fix
        return p;//return the DOM node's native concept of itself, which will expand any relative links into real ones
    }

    // ideally the api returns informative errors, but in the case of failures, let's try to parse the error json, if any, and then make sure we have a standard response if all else fails
    function errorFilter(e){
        console.log(e);
        var msg = 'No response from sever';
        if(e && e.responseJSON){
            return e.responseJSON;
        }
        else{
            try{
                return $.parseJSON(e.responseText);
            }
            catch(error){
                return {status:'fail',code:503, message: msg, error:msg };
            }
        }
    }

    function successFilter(response){
        return (!response || response.status!=="success") ?
            $.Deferred().rejectWith(this, [response]) :
            response;
    }

    // allow any changes to a field that was invalid to clear that custom Error value
    function recheckIfThisIsStillInvalid($field, field, badinput){
        $field.one('change keyup',function(){
            if($field.val()!==badinput){
                field.setCustomValidity('');//we've now cleared the custom error
            }
        });
    }

    function formSuccess(result){
        //"this" is the jquery wrapped $form
        this.trigger('bsd-success',[result]);
        if(this.data('no_redirect')!==true && result.thanks_url){
            wlocation.href = result.thanks_url;
        }
    }

    function formFailure(e){
        //"this" is the jquery wrapped $form
        var $form = this,
            funerror = false,
            errorsAsObject = {};
        if(e && e.field_errors && e.field_errors.length){
            $.each(e.field_errors,function(i,err){
                var $errField = $form.find('[name="'+err.field+'"]'),
                    errField = $errField.get()[0];
                if(err.field==="submit-btn"){
                    e.message = err.message;
                }
                else if(errField && errField.setCustomValidity && interactiveValidity && !$form[0].noValidate && !$form.data('no_html5validate')){
                    errField.setCustomValidity(err.message);//this sets an additional constraint beyond what the browser validated
                    recheckIfThisIsStillInvalid($errField,errField,err.message);//and since we don't know what it is, we at least check to make sure it's no longer what the server has already rejected
                    funerror= true;
                }
                err.$field = $errField;
                errorsAsObject[err.field] = err.message;
                $errField.trigger('invalid', err.message);//and now let's trigger a real event that someone can use to populatre error classes
            });
            if(funerror && interactiveValidity){
                //for this to work, triggering the native validation, we'd need to hit the submit button, not just do a $form.submit()
                $form.find('[type="submit"],[type="image"]').eq(0).click();
            }
        }
        $form.trigger('bsd-error',[e, errorsAsObject]);
    }

    //create a replacement for actually submitting the form directly
    function jsapiSubmit($form, action, ops){
        return function(e){
            //we're going to use jQuery's ajax to actually check if a request is crossDomain or not, rather than using our own test. Then if it is, and the browser doesn't support that, we'll just cancel the request and let the form submit normally
            if($form.data('isPaused')!==true){//allow a means to prevent submission entirely
                var data = $form.serializeObject(),
                    apiaction = action.replace(/\/page\/(signup|s)/,'/page/sapi'),
                    request = $.ajax({
                        url: apiaction,//where to post the form
                        type: 'POST',
                        method: 'POST',
                        dataType: 'json',//no jsonp
                        timeout: ops.timeout||3e4,
                        context: $form,//set the value of "this" for all deferred functions
                        data: data,
                        beforeSend: function(jqxhr, requestsettings){
                            console.log('beforesend check ',jqxhr, requestsettings);
                            console.log(' url: ',requestsettings.url,' xd?: ',requestsettings.crossDomain, ' cors?: ',$.support.cors,' have oldiexdr support?: ',$.oldiexdr,' oldiexdr and protocol match?: ',($.oldiexdr && parseURL(requestsettings.url).protocol===wlocation.protocol));
                            console.log('data',requestsettings.data);
                            if(
                                ops.proxyall ||
                                (
                                    requestsettings.crossDomain &&
                                    !$.support.cors &&
                                    !($.oldiexdr && parseURL(requestsettings.url).protocol===wlocation.protocol)
                                )
                            ){
                                console.log('using proxy',ops.oldproxy);
                                if(ops.oldproxy||ops.proxyall){
                                    requestsettings.url = ops.oldproxy||ops.proxyall;
                                    requestsettings.crossDomain = false;
                                    requestsettings.data += '&purl='+apiaction;
                                    // console.log(function(){
                                    //     window.alert('using proxy:'+ops.oldproxy+' proxying: '+apiaction);
                                    // });
                                }else{
                                    return false;//request is cors but the browser can't handle that, so let the normal form behavior proceed
                                }
                            }
                            e.preventDefault();//cancel the native form submit behavior
                        }
                    });

                //only add the handlers if the request actually happened
                if(request.statusText!=="canceled"){
                    $form.trigger('bsd-submit', data);
                    request
                        .then(successFilter, errorFilter)
                        .done(formSuccess)
                        .fail(formFailure);
                }
            }else{
                e.preventDefault();//cancel the native form submit behavior
            }
        };
    }

    //handle making sure sources in the url end up in the form, like in a native tools signup form
    function normalizeSourceField($form, name, external){
        var $field = $form.find('[name="'+name+'"]'),
            oldval;
        if(!$field.length){
            $field = $('<input/>',{'type':'hidden','name':name}).appendTo($form);
        }
        if(external){
            oldval = $field.val();
            $field.val(
                (
                    (oldval!=="") ?
                        (oldval+',') :
                        ''
                )+external
            );
        }
    }

    /*create the plugin*/
    $.fn.bsdSignup = function(ops){
        return this.each(function(){
            var $form = $(this),
                action = $form.attr('action');//action or self (self is pretty unlikely here, but bwhatever)
            if(ops==="remove"){
                $form.off('submit.bsdsignup').data('bsdsignup',null);//removes the plugin entirely
            }else{
                if($form.is('form') && action.indexOf('page/s')>-1){//only bother if key elements are present
                    if($form.data('bsdsourced')!==true && !ops.nosource){
                        normalizeSourceField($form, sourceString, urlsource);
                        normalizeSourceField($form, subsourceString, urlsubsource);
                        $form.data('bsdsourced', true);
                    }
                    if($.isPlainObject(ops)){
                        $form.data('bsdsignup',ops);
                    }else{
                        ops={};
                    }
                    $form.on('submit.bsdsignup', jsapiSubmit($form, action, ops));
                    console.log('init bsdSignup',{form:$form, interactive_validation:interactiveValidity,options:ops,action:ops.proxy||action});
                }
            }
        });
    };

}(jQuery, window.location));