jQuery(document).ready(function ($) {
  var wpscanReport = {
    pageMargins: [0, 80, 0, 80],

    header: function () {
      const date = new Date();
      const options = {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
      };

      return [
        {
          canvas: [
            {
              type: 'polyline',
              color: '#fff',
              points: [
                { x: 0, y: 0 },
                { x: 327, y: 0 },
                { x: 297, y: 59 },
                { x: 0, y: 59 },
              ],
            },
            {
              type: 'polyline',
              color: '#006699',
              points: [
                { x: 327, y: 0 },
                { x: 357, y: 0 },
                { x: 327, y: 59 },
                { x: 297, y: 59 },
              ],
            },
            {
              type: 'polyline',
              color: '#33CC99',
              points: [
                { x: 357, y: 0 },
                { x: 595.28, y: 0 },
                { x: 595.28, y: 59 },
                { x: 327, y: 59 },
              ],
            },
            {
              type: 'line',
              x1: 0,
              y1: 60,
              x2: 595.28,
              y2: 60,
              lineWidth: 1,
              color: '#D7DFE3',
            },
          ],
        },
        {
          svg: `<svg xmlns="http://www.w3.org/2000/svg" width="159" height="45" viewBox="0 0 159 45">
    <g fill="none">
      <polyline fill="#069" points="29.277 33.719 9.857 22.506 .146 28.112 29.277 44.932 58.409 28.112 48.698 22.506 29.277 33.719"/>
      <polyline fill="#3C9" points="48.698 22.506 58.409 16.9 29.277 .081 .146 16.9 9.857 22.506 29.277 11.293 48.698 22.506"/>
      <path fill="#FFF" d="M48.6984848,22.5062122 L29.2774243,11.2934848 L9.85636368,22.5062122 L19.9818182,28.3522728 C18.7071212,29.5137878 17.4965152,30.6225758 17.2719697,30.8469697 C16.2375757,31.8813637 16.2375757,33.5021212 17.2719697,34.5365151 C17.7734848,35.0378788 18.429697,35.3142424 19.1198485,35.3142424 C19.12,35.3142424 19.12,35.3142424 19.12,35.3142424 C19.8060606,35.3142424 20.46,35.0380303 20.9616666,34.5365151 C21.2425758,34.2554546 22.9107576,32.4274243 24.3325758,30.8640909 L29.2774243,33.7190909 L48.6984848,22.5062122"/>
      <path fill="#000" d="M33.2386364,22.5062122 C33.2384848,23.5212121 32.8521212,24.5362121 32.0796969,25.3090909 C31.3312121,26.0572728 30.3362122,26.4692425 29.2775757,26.4692425 L29.2774243,26.4692425 L29.2774243,25.4590909 L29.2775757,25.4592424 C30.0663636,25.4592424 30.8078788,25.1522727 31.3651516,24.595 C31.9409091,24.0189394 32.2287879,23.2625758 32.2289394,22.5062122 L33.2386364,22.5062122 Z M29.2771212,27.5034848 C27.949394,27.5033334 26.7004545,26.9859091 25.7610606,26.0466666 C24.8218182,25.1074242 24.304394,23.8584848 24.3042424,22.53 C24.3042424,19.7880303 26.535303,17.5569697 29.2775757,17.5568182 C30.6059091,17.5572727 31.8551515,18.0748485 32.7945454,19.0142424 C33.7337878,19.9533334 34.2509091,21.2021213 34.2509091,22.530303 C34.2509091,25.2718182 32.019697,27.5028788 29.2771212,27.5034848 Z M29.2774243,16.0416667 C25.6936363,16.0418182 22.7890909,18.9462121 22.7890909,22.5301515 C22.7892425,24.0565152 23.3177273,25.4586363 24.1998485,26.5666667 C23.7959091,26.9328788 18.7875758,31.4740909 18.3433334,31.9183333 C17.8804545,32.3812121 17.9162122,33.0380303 18.3433334,33.4651515 C18.7706061,33.8922727 19.4274242,33.9280303 19.8901516,33.4651515 C20.3345455,33.0209091 24.8763636,28.0118182 25.2419697,27.6086363 C26.349697,28.4903031 27.7515151,29.0184848 29.2775757,29.0186364 C32.8606061,29.0178788 35.7660606,26.1142424 35.7662121,22.530303 C35.7660606,18.9463637 32.8606061,16.0427273 29.2774243,16.0416667 L29.2774243,16.0416667 Z"/>
      <polyline fill="#000" points="90.671 14.721 88.359 14.721 88.359 15.824 90.28 16.114 87.413 26.332 84.298 16.095 86.517 15.824 86.517 14.721 79.826 14.721 79.826 15.824 81.87 16.069 78.675 26.266 75.995 16.052 77.95 15.824 77.95 14.721 71.131 14.721 71.131 15.824 72.834 16.012 77.057 30.396 78.671 30.396 82.223 18.926 85.936 30.396 87.564 30.396 91.612 16.117 93.513 15.824 93.513 14.721 90.671 14.721"/>
      <path fill="#000" d="M101.126061,23.0351515 L99.9803034,23.0351515 L99.9803034,21.9971212 C99.9803034,19.9516666 99.9828792,17.925 100.02303,15.9386364 L101.032273,15.9386364 C103.467272,15.9386364 104.674394,17.1495454 104.674394,19.3659091 C104.674394,21.5525758 103.599394,23.0351515 101.126061,23.0351515 Z M101.271363,14.7210606 L94.9231818,14.7210606 L94.9231818,15.8237878 L96.8966667,16.0386364 C96.9353028,18.0236363 96.9375762,20.0178787 96.9375762,21.9971212 L96.9375762,23.0154546 C96.9375762,25.0265152 96.9353028,27.019697 96.8971212,28.9739394 L94.9231818,29.1889394 L94.9231818,30.2915152 L102.167122,30.2915152 L102.167122,29.1889394 L100.018939,28.955 C99.9868185,27.32 99.9812124,25.7442424 99.9804546,24.2354545 L100.937576,24.2354545 C105.880757,24.2354545 107.645,22.0292424 107.645,19.3745454 C107.645,16.6025758 105.675757,14.7210606 101.271363,14.7210606 L101.271363,14.7210606 Z M119.694545,26.0892424 C119.694545,28.8589394 117.474546,30.6865151 113.85409,30.6865151 C112.093788,30.6865151 110.242576,30.2095454 109.025,29.4513637 L109.165909,26.0619697 L110.970455,26.0619697 L111.436515,28.8956061 C112.171819,29.235 112.870454,29.4010606 113.81803,29.4010606 C115.763485,29.4010606 116.949243,28.4028788 116.949243,26.8563636 C116.949243,25.4772727 116.189091,24.8569697 114.465909,24.145 L113.522273,23.7412121 C110.938637,22.6387879 109.321515,21.2601515 109.321515,18.7628788 C109.321515,16.0033333 111.598333,14.3260606 114.871819,14.3260606 C116.496212,14.3260606 117.929697,14.7930303 119.007727,15.5440909 L118.841212,18.7159091 L117.05394,18.7159091 L116.583484,16.0015152 C116.031818,15.7360606 115.481818,15.6115151 114.834394,15.6115151 C113.253637,15.6115151 112.023788,16.4924242 112.023788,18.0115151 C112.023788,19.3695455 112.874243,20.1259091 114.423788,20.7660606 L115.43394,21.1956061 C118.480454,22.4701515 119.694545,23.7969697 119.694545,26.0892424 L119.694545,26.0892424 Z M130.692425,27.1925758 L131.385,27.6981818 C130.613485,29.6289394 129.016667,30.6224242 126.801667,30.6224242 C123.594697,30.6224242 121.257424,28.4725758 121.257424,24.700606 C121.257424,20.9381818 123.968334,18.7233333 127.123333,18.7233333 C129.410455,18.7233333 131.147273,20.1434848 131.383485,21.9492424 C131.19697,22.665303 130.776212,22.9986364 130.06,22.9986364 C129.20803,22.9986364 128.670455,22.4775758 128.53197,21.3478788 L128.233636,19.945303 C127.941364,19.8865151 127.653333,19.8571212 127.365909,19.8571212 C125.677424,19.8571212 124.213485,21.2869697 124.213485,24.3775758 C124.213485,27.2056061 125.648031,28.7560607 127.733788,28.7560607 C128.974546,28.7560607 129.990606,28.1678788 130.692425,27.1925758 L130.692425,27.1925758 Z M137.395303,28.9072727 C136.462121,28.9072727 135.743939,28.3884848 135.743939,27.1790909 C135.743939,26.2831819 136.211212,25.4428788 137.789394,24.8109091 C138.187121,24.6401515 138.790303,24.4381819 139.436364,24.250606 L139.436364,27.9486364 C138.41591,28.6786363 138.004242,28.9072727 137.395303,28.9072727 Z M142.906061,29.1395454 C142.469697,29.1395454 142.209091,28.8415151 142.209091,28.0869697 L142.209091,22.964697 C142.209091,19.9127272 141.027273,18.7233333 138.308182,18.7233333 C135.435606,18.7233333 133.621212,19.9384849 133.338939,21.8084848 C133.455,22.49 133.925152,22.8678788 134.662272,22.8678788 C135.433788,22.8678788 135.992576,22.3631818 136.126364,21.2343939 L136.400909,19.9403031 C136.773485,19.8775758 137.092879,19.8571212 137.384546,19.8571212 C138.907727,19.8571212 139.436364,20.4462122 139.436364,22.6343939 L139.436364,23.3022727 C138.625606,23.5116667 137.801516,23.7490909 137.20197,23.9439394 C133.815758,25.0422727 132.999546,26.1587879 132.999546,27.6542424 C132.999546,29.5934848 134.345152,30.6224242 136.11,30.6224242 C137.599849,30.6224242 138.364242,29.9981818 139.524242,28.8192424 C139.792424,29.9193939 140.60303,30.5778788 141.84697,30.5778788 C142.963637,30.5778788 143.701515,30.1780303 144.265152,29.0878788 L143.718182,28.6233333 C143.451515,28.9715152 143.227273,29.1395454 142.906061,29.1395454 L142.906061,29.1395454 Z M158.407576,29.3322727 L158.407576,30.2915152 L152.604545,30.2915152 L152.604545,29.3322727 L154.092425,29.034697 C154.116667,27.8821212 154.127273,26.4113636 154.127273,25.3627273 L154.127273,23.1412121 C154.127273,21.3031818 153.659091,20.685 152.492424,20.685 C151.651516,20.685 150.778788,21.0713637 149.851516,21.8586363 L149.851516,25.3627273 C149.851516,26.384697 149.863637,27.8801515 149.886364,29.0466667 L151.272727,29.3322727 L151.272727,30.2915152 L145.477273,30.2915152 L145.477273,29.3322727 L146.992424,29.0328788 C147.015151,27.8684849 147.027272,26.3806061 147.027272,25.3627273 L147.027272,24.130303 C147.027272,22.839394 147.00303,22.2322728 146.956061,21.2934848 L145.30303,21.0824242 L145.30303,20.2092424 L149.227273,18.7233333 L149.604546,18.9668182 L149.772727,20.7771212 C150.992424,19.4034848 152.351515,18.7233333 153.765151,18.7233333 C155.792425,18.7233333 156.95303,20.010303 156.95303,22.9104545 L156.95303,25.3627273 C156.95303,26.4145454 156.963636,27.8909091 156.987878,29.0448485 L158.407576,29.3322727 L158.407576,29.3322727 Z"/>
    </g>
  </svg>`,
          width: 112,
          absolutePosition: { x: 0, y: 0 },
          relativePosition: { x: 40, y: 10 },
          alignment: 'left',
        },
        {
          text: 'wpscan.com',
          link: 'http://wpscan.com',
          fontSize: 8,
          absolutePosition: { x: 0, y: 0 },
          relativePosition: { x: 93, y: 35 },
        },
        {
          text: 'Vulnerability Report',
          fontSize: 16,
          bold: true,
          color: '#ffffff',
          alignment: 'right',
          absolutePosition: { x: 0, y: 0 },
          relativePosition: { x: -40, y: 13 },
        },
        {
          text: new Intl.DateTimeFormat('en-GB', options).format(date),
          fontSize: 10,
          color: '#ffffff',
          absolutePosition: { x: 0, y: 0 },
          relativePosition: { x: -40, y: 32 },
          alignment: 'right',
        },
      ];
    },
    footer: function (currentPage) {
      return [
        {
          canvas: [
            {
              type: 'polyline',
              color: '#282d41',
              points: [
                { x: 0, y: 26 },
                { x: 0, y: 80 },
                { x: 595.28, y: 80 },
              ],
            },
          ],
        },
        {
          text: currentPage,
          alignment: 'right',
          fontSize: 8,
          absolutePosition: { x: 0, y: 0 },
          relativePosition: { x: -40, y: 30 },
        },
      ];
    },
    content: [],
  };

  /**
   * Fonts setup
   */
  pdfMake.fonts = {
    Montserrat: {
      normal: 'Montserrat-Regular.ttf',
      bold: 'Montserrat-Bold.ttf',
      italics: 'Montserrat-Medium.ttf',
      bolditalics: 'Montserrat-Medium.ttf',
    },
  };

  /**
   * Background
   */
  wpscanReport.background = function () {
    return {
      canvas: [
        {
          type: 'rect',
          x: 0,
          y: 0,
          w: 595.28,
          h: 841.89,
          color: '#f1f4f6',
        },
      ],
    };
  };

  /**
   * Styles
   */
  wpscanReport.styles = {
    wordpressHeader: {
      fontSize: 14,
      bold: true,
      margin: [0, -15, 0, 0],
    },
    header: {
      fontSize: 14,
      bold: true,
      margin: [40, 20, 0, 0],
    },
    tableLine: {
      fillColor: '#006699 ',
      margin: [0, -8],
      fontSize: 1,
    },
    WPTableLine: {
      fillColor: '#32B488 ',
      margin: [0, -8],
      fontSize: 1,
    },
    tableHeader: {
      fillColor: '#f6f6f6',
      margin: [10, 10],
      fontSize: 10,
      bold: true,
    },
    resTable: {
      margin: [10, 7, 10, 10],
      fillColor: '#ffffff',
      fontSize: 10,
      italics: true,
    },
    metadata: {
      fontSize: 10,
      bold: true,
      color: '#333333',
    },
  };

  /**
   * Default style
   */
  wpscanReport.defaultStyle = {
    color: '#333333',
    font: 'Montserrat',
  };

  /**
   * border color
   */

  var borderColor = ['#e5e5e5', '#e5e5e5', '#e5e5e5', '#e5e5e5'];

  /**
   * Tables
   */
  $('.wpscan-report-section').each(function () {
    let is_security_checks = false;
    let is_wordpress_section =
      $(this).find('h3').first().text().trim() === 'WordPress';

    // Table title

    const sectionTitle = () => {
      if (!is_wordpress_section) {
        wpscanReport.content.push({
          text: $(this).find('h3').first().text(),
          style: 'header',
        });
      }
    };

    const wordpressTitle = () => {
      if (is_wordpress_section) {
        return {
          text: $(this).find('h3').first().text(),
          style: 'wordpressHeader',
        };
      }
    };

    if ($(this).hasClass('security-checks')) {
      is_security_checks = true;
    }

    /**
     * Table setup
     */

    let table = {
      table: {
        headerRows: 2,
        widths: [],
        body: [[], []],
      },
    };

    let wordpressTable = {
      stack: [
        {
          canvas: [
            {
              type: 'rect',
              x: 0,
              y: 00,
              w: 595.28,
              h: 30,
              color: '#ECF8F1',
            },
          ],
          relativePosition: { x: 0, y: -25 },
        },
        {
          table: {
            widths: [32, '*', 32],
            body: [
              [
                {
                  text: ' ',
                  border: [false, false, false, false],
                  fillColor: '#ECF8F1',
                },
                {
                  text: ' ',
                  border: [false, false, false, false],
                  fillColor: '#ECF8F1',
                },
                {
                  text: ' ',
                  border: [false, false, false, false],
                  fillColor: '#ECF8F1',
                },
              ],
              [
                {
                  text: ' ',
                  border: [false, false, false, false],
                  fillColor: '#ECF8F1',
                },
                {
                  stack: [wordpressTitle(), table],
                  border: [false, false, false, false],
                  fillColor: '#ECF8F1',
                },
                {
                  text: ' ',
                  border: [false, false, false, false],
                  fillColor: '#ECF8F1',
                },
              ],
              [
                {
                  text: ' ',
                  border: [false, false, false, true],
                  borderColor: ['#D7DFE3', '#D7DFE3', '#D7DFE3', '#D7DFE3'],

                  margin: [0, 10],
                  fillColor: '#ECF8F1',
                },
                {
                  text: ' ',
                  border: [false, false, false, true],
                  borderColor: ['#D7DFE3', '#D7DFE3', '#D7DFE3', '#D7DFE3'],

                  margin: [0, 10],
                  fillColor: '#ECF8F1',
                },
                {
                  text: ' ',
                  border: [false, false, false, true],
                  borderColor: ['#D7DFE3', '#D7DFE3', '#D7DFE3', '#D7DFE3'],

                  margin: [0, 10],
                  fillColor: '#ECF8F1',
                },
              ],
            ],
          },
        },
      ],
    };

    let mainTable = {
      stack: [
        sectionTitle(),
        {
          table: {
            widths: [32, '*', 32],
            body: [[{}, table, {}]],
          },
          layout: 'noBorders',
        },
      ],
    };

    /**
     * Table head
     */

    const colSpan = is_security_checks ? 2 : 3;

    const topTableBorder = is_wordpress_section ? 'WPTableLine' : 'tableLine';

    // Name
    table.table.body[1].push({
      text: 'Name',
      style: 'tableHeader',
      borderColor,
    });
    table.table.widths.push(149);

    // Version
    if (!is_security_checks) {
      table.table.body[1].push({
        text: 'Version',
        style: 'tableHeader',
        borderColor,
      });
      table.table.widths.push(79);
    }

    // Vulnerabilities
    table.table.body[1].push({
      text: 'Vulnerabilities',
      style: 'tableHeader',
      borderColor,
    });
    table.table.widths.push('*');

    table.table.body[0].push({
      text: ' ',
      style: topTableBorder,
      colSpan: colSpan,
      border: [false, false, false, false],
    });

    if (!is_security_checks) {
      table.table.body[0].push({});
    }

    table.table.body[0].push({});

    // Add rows
    $(this)
      .find('table tbody')
      .children()
      .each(function () {
        let row = [];

        // Item name
        let itemTitle = is_wordpress_section ? 'WordPress' : $(this).find('.plugin-title strong').text().trim();
        
        if ($(this).find('.plugin-title .item-closed').length) {
          itemTitle =
            itemTitle +
            ' - ' +
            $(this).find('.plugin-title .item-closed').text();
        }

        row.push({
          text: itemTitle,
          style: 'resTable',
          borderColor,
          lineHeight: 2,
        });

        // Item version
        let itemVersion = is_wordpress_section ? $(this).find('#wordpress-version').text().trim() : $(this).find('.plugin-title .item-version span').text().trim();

        if (!is_security_checks) {
          row.push({
            text: itemVersion,
            style: 'resTable',
            borderColor,
          });
        }

        // Item vulnerabilities
        if ($(this).find('.vulnerabilities .vulnerability').length) {
          let col = {
            stack: [],
            style: 'resTable',
            lineHeight: 2,
            borderColor,
          };

          // for each vulnerability
          $(this)
            .find('.vulnerabilities .vulnerability')
            .each(function () {
              let item = $(this).clone();
              let title     = item.find('.vulnerability-title').text().trim();
              let status    = item.find('.vulnerability-status').text().trim();
              let severity  = item.find('.vulnerability-severity span').text().trim();
              let link_text = item.find('.vulnerability-link').text().trim();
              let link_href = item.find('.vulnerability-link a').attr('href');

              let vulnerability_text = [
                { text: title, style: 'resTable' },
                { text: status, style: 'resTable' },
                { text: severity.charAt(0).toUpperCase() + severity.slice(1), style: 'resTable' },
                { text: link_text, link: link_href, style: 'resTable' }
              ]

              col.stack.push( vulnerability_text );
            });

          row.push(col);
        } else {
          // No vulnerabilities found
          row.push({
            text: $(this).find('.vulnerabilities').text().trim(),
            style: 'resTable',
            borderColor,
          });
        }

        table.table.body.push(row);
      });

    // Push the table
    is_wordpress_section
      ? wpscanReport.content.push(wordpressTable)
      : wpscanReport.content.push(mainTable);
  });

  // Download
  $('.download-report').on('click', function () {
    let dt = new Date().toJSON().slice(0, 10);
    // pdfMake.createPdf(wpscanReport).open();
    pdfMake.createPdf(wpscanReport).download(dt + '-wpscan-report.pdf');
  });
});
