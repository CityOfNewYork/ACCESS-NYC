/**
 * Maps change events from the Custom Translate element to the Google Translate
 * element. Observes the html lang attribute and switches stylesheets based on
 * the changed language (if the stylesheet exists).
 *
 * @class
 */
class TranslateElement {
  /**
   * The Constructor
   *
   * @param   {Object}  element  The container of the Google Translate Element
   *
   * @return  {Object}  An instance of TranslateElement
   */
  constructor(element) {
    this.element = element;

    this.control = document.querySelector(TranslateElement.selectors.control);

    this.html = document.querySelector(TranslateElement.selectors.html);

    /**
     * Observe the HTML tag for language switching
     */
    new MutationObserver(mutations => {
      this.observer(mutations);
    }).observe(this.html, {
      attributes: true
    });

    /**
     * Listen for the change event on the select controller
     */
    this.control.addEventListener('change', event => {
      this.change(event);
    });

    return this;
  }

  /**
   * Prepend the language path to an internal link
   *
   * @param   {Object}  event  The link click event
   */
  click(event) {
    let origin = window.location.origin;
    let link = (event.target.matches('a'))
      ? event.target : event.target.closest('a');

    let lang = document.querySelector(TranslateElement.selectors.html)
      .getAttribute('lang');

    let slang = (lang === TranslateElement.maps['zh-hant'])
        ? 'zh-hant' : lang;

    let slink = link.href.replace(origin, `${origin}/${slang}`);
    let target = (link.target === '_blank') ? link.target : '_self';

    let samesite = link.href.includes(origin);
    let samepage = (link.pathname === window.location.pathname);

    if (samesite && !samepage) {
      event.preventDefault();

      window.open(slink, target);
    }
  }

  /**
   * The observer method for the HTML lang attribute;
   * 1. Update the select if the original language (English) is restored
   * 2. Set reading direction of the document
   * 3. Switch to the appropriate language stylesheet if it exists
   * 4. Add the click event for prepending the language path to internal link
   *
   * @param   {Array}  mutations  List of Mutations from MutationObserver
   */
  observer(mutations) {
    let langs = mutations.filter(m => m.attributeName === 'lang');
    let stylesheets = TranslateElement.stylesheets;

    if (langs.length) {
      let lang = langs[0].target.lang;

      // Update the select if the original language (English) is restored
      this.control.value = (TranslateElement.restore.includes(lang))
        ? 'restore' : lang;

      // Set reading direction of the document
      this.html.setAttribute('direction',
        (TranslateElement.rtl.includes(lang)) ? 'rtl' : 'ltr');

      // Switch to the appropriate language stylesheet if it exists
      let slang = (lang === TranslateElement.maps['zh-hant'])
        ? 'zh-hant' : lang;

      let stylesheet = stylesheets.filter(s => s.includes(`style-${slang}`));
      let latin = stylesheets.filter(s => s.includes('style-default'));
      let switched = (stylesheet.length) ? stylesheet[0] : latin[0];

      document.querySelector(TranslateElement.selectors.stylesheet)
        .setAttribute('href', switched);

      // Add the click event for prepending the language path to internal link
      document.querySelectorAll('a').forEach(link => {
        if (TranslateElement.restore.includes(lang)) {
          link.removeEventListener('click', this.click);
        } else {
          link.addEventListener('click', this.click);
        }
      });
    }
  }

  /**
   * The select change event mapping from custom element to google element
   *
   * @param   {Object}  event  The original change event of the custom element
   */
  change(event) {
    let select = this.element.querySelector('select');

    select.value = event.target.value;

    let change;

    if (typeof(Event) === 'function') {
      change = new Event('change');
    } else {
      change = document.createEvent('Event');
      change.initEvent('change', true, true);
    }

    select.dispatchEvent(change);
  }
}

/** Array of existing site stylesheets to switch */
TranslateElement.stylesheets = window.STYLESHEETS;

/** Right to left languages */
TranslateElement.rtl = ['ar', 'ur'];

/** Values that trigger the restore value change in the custom element */
TranslateElement.restore = ['auto', 'en'];

/** Google Translate element selector */
TranslateElement.selector = '#js-google-translate';

/** Collection of component selectors */
TranslateElement.selectors = {
  control: '#js-google-translate-control',
  html: 'html',
  stylesheet: '#style-default-css'
};

/** Language mappings from the site to the Google Translate element */
TranslateElement.maps = {
  'zh-hant': 'zh-CN'
};

export default TranslateElement;
