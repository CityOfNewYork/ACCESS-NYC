const { registerBlockType } = wp.blocks;

const __ = wp.i18n.__;

/**
 * Elements and styles
 */

// Common attributes for HTML email tables
const tableAttrs = {
  align: 'center',
  border: '0',
  cellPadding: '0',
  cellSpacing: '0',
  role: 'presentation'
};

// Common link styling
const linkStyles = {
  textDecoration: 'none',
  lineHeight: '100%',
  background: '#156b3d',
  color: '#ffffff',
  fontFamily: 'Trebuchet MS, sans-serif',
  fontSize: '20px',
  fontWeight: 'bold',
  textTransform: 'none',
  margin: '0px'
};

// The left pointing arrow > element that appears in the button
let arrowElement = wp.element.createElement('span', {
    style: {
      paddingLeft: '10px',
      fontSize: '14px'
    }
  }, wp.element.createElement(
    'img', {
      src: 'https://access.nyc.gov/wp-content/themes/access/assets/img/email/right-arrow.png',
      height: '15',
      width: '10'
    }
  )
);

/**
 * The template function for the button element.
 * @param   {element}  linkElement  The link element will be different in the edit and save function
 * @param   {element}  urlEditor    An optional URL editor input element. Only from the edit function.
 * @return  {element}               A full html email button element.
 */
const createButton = (linkElement, urlEditor = null) => {
  // The table wrapper for the button
  let tableElement = wp.element.createElement(
    'table', {
      ...tableAttrs,
      style: {
        borderCollapse: 'separate',
        padding: '16px 0px 32px',
        float: 'none'
      }
    }, wp.element.createElement(
      'tbody', null, wp.element.createElement(
        'tr', null, wp.element.createElement(
          'td', {
            align: 'center',
            bgcolor: '#156b3d',
            style: {
              border: 'none',
              borderRadius: '10px',
              color: '#ffffff',
              cursor: 'auto',
              padding: '16px 24px'
            },
            valign: 'middle'
          }, linkElement
        )
      )
    )
  );

  // Create wrapper for the table button and return element
  return wp.element.createElement('div', null,
    wp.element.createElement(
      'table', {
        ...tableAttrs,
        width: '100%'
      }, wp.element.createElement(
        'tbody', null, wp.element.createElement(
          'tr', null, wp.element.createElement(
            'td', {
              align: 'center',
              style: {
                wordBreak: 'break-word',
                fontSize: '0px'
              }
            }, tableElement
          )
        )
      )
    ),
    urlEditor
  );
}

/**
 * Block registration and edit/save functionality.
 */
registerBlockType('access/smnyc-email-button', {
  /**
   * Block Registration Settings
   * @link https://developer.wordpress.org/block-editor/developers/block-api/block-registration/
   */
  title: __('SMNYC Email Button', 'access-blocks'),
  description: __('An HTML Email friendly button for Send Me NYC templates.'),
  keywords: [
    __('email', 'access-blocks'),
    __('button', 'access-blocks'),
    __('smnyc', 'access-blocks')
  ],
  category: 'layout',
  supports: {
    align: false,
    anchor: false,
    customClassName: false
  },
  attributes: {
    content: {
      type: 'string'
    },
    url: {
      type: 'string'
    }
  },

  /**
   * The edit function describes the structure of your block in the context of the editor.
   * @link https://developer.wordpress.org/block-editor/developers/block-api/block-edit-save/#edit
   * @param   {object}  props  mixed, see documentation
   * @return  {array}          The button block editor element, and the url field in inspector controls
   */
  edit: (props) => {
    let linkElement = wp.element.createElement('a',
      {style: linkStyles},
      (!props.attributes.content) ? 'Add text in the block inspector...' : props.attributes.content
    );

    let urlEditor = wp.element.createElement(wp.editor.URLInputButton, {
      className: props.className,
      url: props.attributes.url,
      onChange: function(url, post) {
        props.setAttributes({
          url: url
        });
      }
    });

    let button = createButton(linkElement, urlEditor);

    /**
     * Button text field for the instpector panel
     */
    let buttonInspector = wp.element.createElement(
      wp.editor.InspectorControls, {
        key: 'inspector'
      }, wp.element.createElement(
        wp.components.PanelBody, {
          title: __('Button Text', 'access-blocks'),
          className: 'block-gb-cta-link',
          initialOpen: true,
        }, wp.element.createElement(
          wp.components.TextControl, {
            type: 'text',
            label: __('Add text for the button.', 'access-blocks'),
            value: props.attributes.content,
            onChange: (newContent) => {
              props.setAttributes({
                content: newContent
              });
            },
          }
        )
      )
    );

    /**
     * Link text field for the inspector panel
     */
    let linkInspector = wp.element.createElement(
      wp.editor.InspectorControls, {
        key: 'inspector'
      }, wp.element.createElement(
        wp.components.PanelBody, {
          title: __('Button URL', 'access-blocks'),
          className: 'block-gb-cta-link',
          initialOpen: true,
        }, wp.element.createElement(
          wp.components.TextControl, {
            type: 'url',
            label: __('Enter the destination URL for the button. Use the \
              template tag "{{ URL }}" to represent the default url that is \
              created by the screener results page. Use "{{ BITLY_URL }}" for \
              the shortened version of the url.', 'access-blocks'),
            value: props.attributes.url,
            onChange: (newUrl) => {
              props.setAttributes({
                url: newUrl
              });
            },
          }
        )
      )
    );

    return [button, buttonInspector, linkInspector];
  },

  /**
   * The save function defines the way in which the different attributes should
   * be combined into the final markup, which is then serialized into post_content.
   * @param   {object}  props  mixed, see documentation
   * @return  {object}         Returns the React Element template for the HTML email button
   */
  save: (props) => {
    return createButton(wp.element.createElement('a', {
      href: props.attributes.url,
      style: linkStyles
    }, props.attributes.content
    ));
  }
});