const { registerBlockType } = wp.blocks;

const __ = wp.i18n.__;

registerBlockType('access/smnyc-email-button', {
  /**
   * Block Registration Settings
   * @link https://developer.wordpress.org/block-editor/developers/block-api/block-registration/
   */
  title: __('SMNYC Email Button', 'access-blocks'),
  description: __('An HTML Email friendly button for Send Me NYC templates. When creating a new button, it will appear as a regular text field. Add the text for the button in the editor and the url below. Note: after saving and revisiting the post, the button block will appear as invalid. However, It can be converted into an HTML Block to preserve the button.'),
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
      type: 'string',
      source: 'html'
    },
    url: {
      type: 'url'
    }
  },
  /**
   * The edit function describes the structure of your block in the context of the editor.
   * @link https://developer.wordpress.org/block-editor/developers/block-api/block-edit-save/#edit
   * @param   {object}  props  mixed, see documentation
   * @return  {array}          The button block editor element, and the url field in inspector controls
   */
  edit: (props) => {
    let button = wp.element.createElement(
      wp.editor.RichText, {
        placeholder: __('Add Text...', 'access-blocks'),
        value: props.attributes.content,
        onChange: (content) => {
          props.setAttributes({
            content: content
          });
        }
      }, props.attributes.content
    );

    let link = wp.element.createElement(
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
            label: __('Enter the destination URL for the button. Use the template tag "{{ URL }}" to represent the default url that is created by the screener results page.', 'access-blocks'),
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

    return [button, link];
  },
  /**
   * The save function defines the way in which the different attributes should
   * be combined into the final markup, which is then serialized into post_content.
   * @param   {object}  props  mixed, see documentation
   * @return  {object}         Returns the React Element template for the HTML email button
   */
  save: (props) => {
    // Common attributes for HTML email tables
    let tableAttrs = {
      align: 'left',
      border: '0',
      cellpadding: '0',
      cellspacing: '0',
      role: 'presentation'
    };

    // The left pointing arrow > element that appears in the button
    let arrowElement = wp.element.createElement('span', {
        style: {
          paddingLeft: '10px',
          fontSize: '14px'
        }
      }, wp.element.createElement(
        'img', {
          height: '15',
          src: 'https://access.nyc.gov/wp-content/themes/access/assets/img/email/right-arrow.png',
          width: '10'
        }
      )
    );

    // The a tag of the button
    let linkElement = wp.element.createElement(
      'a', {
        href: props.attributes.url,
        style: {
          textDecoration: 'none',
          lineHeight: '100%',
          background: '#156b3d',
          color: '#ffffff',
          fontFamily: 'Trebuchet MS, sans-serif',
          fontSize: '20px',
          fontWeight: 'bold',
          textTransform: 'none',
          margin: '0px'
        },
        target: '_blank'
      }, props.attributes.content, arrowElement
    );

    // The table wrapper for the button
    let tableElement = wp.element.createElement(
      'table', {
        ...tableAttrs,
        style: {
          borderCollapse: 'separate',
          padding: '50px 0px'
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
                padding: '25px 15px'
              },
              valign: 'middle'
            }, linkElement
          )
        )
      )
    );

    // Create wrapper for the table button and return element
    return wp.element.createElement(
      'table', {
        ...tableAttrs,
        width: '100%'
      }, wp.element.createElement(
        'tbody', null, wp.element.createElement(
          'tr', null, wp.element.createElement(
            'td', {
              align: 'left',
              style: {
                wordBreak: 'break-word',
                fontSize: '0px'
              }
            }, tableElement
          )
        )
      )
    );
  }
});