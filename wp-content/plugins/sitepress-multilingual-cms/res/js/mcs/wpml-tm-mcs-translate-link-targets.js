/* global jQuery */
/* global ajaxurl */

( function ( $ ) {
  const self = this

  const init = function () {
    self.nonce = $( '[name=wpml-translate-link-targets]' ).val()
    self.button = $( '#wpml-scan-link-targets' )
    self.spinner = self.button.parent().find( '.spinner' )
    self.message = self.button.parent().find( '.results' )

    self.button.on( 'click', run )
  }

  const run = async function () {
    self.button.prop( 'disabled', true )
    self.numberFixed = 0

    try {
      await scanLinks()
      await replaceLinksInPosts()
      await replaceLinksInStrings()

      // Done, enable the button and show the complete message.
      self.button.prop( 'disabled', false )
      self.message.html(
        self.button.data( 'complete-message' ).replace( '%s', self.numberFixed )
      )
    } catch ( e ) {
      generalError()
    }
  }

  const scanLinks = async function () {
    if ( 'postCount' in self && 'stringCount' in self ) {
      return
    }
    // Show "Scanning now, please wait..." message.
    self.message.html( self.button.data( 'scanning-message' ) )

    // Get the total counts of links and strings to be replaced.
    const response = await serverRequest( 'WPML_Ajax_Scan_Link_Targets' )
    self.postCount = parseInt( response.data.post_count )
    self.stringCount = parseInt( response.data.string_count )
  }

  const replaceLinks = async function ( total, action, messageProcess ) {
    // Total counts available, start replacing links...
    messageProcess( total )

    let start = 0
    let numberLeft = total

    while ( numberLeft > 0 ) {
      const response = await serverRequest(
        action,
        {
          last_processed: start,
          number_to_process: 10,
        }
      )

      self.numberFixed += parseInt( response.data.links_fixed )

      start = parseInt( response.data.last_processed ) + 1
      numberLeft = parseInt( response.data.number_left )

      messageProcess( numberLeft )
    }
  }

  const replaceLinksInPosts = async function () {
    if ( self.postCount === 0 ) {
      return
    }

    return await replaceLinks(
      self.postCount,
      'WPML_Ajax_Update_Link_Targets_In_Posts',
      messageProgressPosts
    )
  }

  const replaceLinksInStrings = async function () {
    if ( self.stringCount === 0 ) {
      return
    }

    return await replaceLinks(
      self.stringCount,
      'WPML_Ajax_Update_Link_Targets_In_Strings',
      messageProgressStrings
    )
  }

  const serverRequest = async function ( action, data ) {
    data = data || {}
    showSpinner()

    return $.ajax( {
      url: ajaxurl,
      method: 'POST',
      data: {
        nonce: self.nonce,
        action,
        ...data
      },
      success: function ( response ) {
        if ( !response.success ) {
          console.log( response )
          throw new Error()
        }
      },
      error: function ( response ) {
        console.log( response )
        throw new Error()
      },
      complete: function () {
        hideSpinner()
      }
    } )
  }

  const messageProgress = function ( type, numberLeft, total ) {
    const message = self.button.data( type + '-message' )
      .replace( '%1$s', total - numberLeft )
      .replace( '%2$s', total )

    self.message.html( message )
  }

  const messageProgressPosts = function ( numberLeft ) {
    messageProgress( 'post', numberLeft, self.postCount )
  }

  const messageProgressStrings = function ( numberLeft ) {
    messageProgress( 'string', numberLeft, self.stringCount )
  }

  const showSpinner = function () {
    self.showSpinner = true
    self.spinner.css( 'visibility', 'visible' )
  }

  const hideSpinner = function () {
    self.showSpinner = false

    // Add a little delay for hidding the spinner, to prevent spinner
    // flickering on recursive serverRequests.
    setTimeout(
      function () {
        if ( self.showSpinner === true ) {
          // Another server requests was made. Keep the spinner displayed.
          return
        }
        self.spinner.css( 'visibility', 'hidden' )
      },
      100
    )
  }

  const generalError = function () {
    // Most probably a nonce problem. Suggest to reload page.
    self.message.html( self.button.data( 'error-message' ) )
  }

  init()
} )( jQuery )
