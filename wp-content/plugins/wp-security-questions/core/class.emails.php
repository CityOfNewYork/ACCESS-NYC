<?php

if(!class_exists('FC_Email')) {
	
	class FC_Email {

	private $email = array();

	public function __construct() {

	}

	function template_header() {
		$head = ' <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta charset="utf-8"> <!-- utf-8 works for most cases -->
<meta name="viewport" content="width=device-width"> <!-- Forcing initial-scale shouldn\'t be necessary -->
<meta http-equiv="X-UA-Compatible" content="IE=edge"> <!-- Use the latest (edge) version of IE rendering engine -->
<meta name="x-apple-disable-message-reformatting">  <!-- Disable auto-scale in iOS 10 Mail entirely -->
<title></title> <!-- The title tag shows in email notifications, like Android 4.4. -->

<!-- Web Font / @font-face : BEGIN -->
<!-- NOTE: If web fonts are not required, lines 9 - 26 can be safely removed. -->

<!-- Desktop Outlook chokes on web font references and defaults to Times New Roman, so we force a safe fallback font. -->
<!--[if mso]>
<style>
* {
font-family: sans-serif !important;
}
</style>
<![endif]-->

<!-- All other clients get the webfont reference; some will render the font and others will silently fail to the fallbacks. More on that here: http://stylecampaign.com/blog/2015/02/webfont-support-in-email/ -->
<!--[if !mso]><!-->
<!-- insert web font reference, eg: <link href="https://fonts.googleapis.com/css?family=Roboto:400,700" rel="stylesheet" type="text/css"> -->
<!--<![endif]-->

<!-- Web Font / @font-face : END -->

<!-- CSS Reset -->
<style>

/* What it does: Remove spaces around the email design added by some email clients. */
/* Beware: It can remove the padding / margin and add a background color to the compose a reply window. */
html,
body {
margin: 0 auto !important;
padding: 0 !important;
height: 100% !important;
width: 100% !important;
}

/* What it does: Stops email clients resizing small text. */
* {
-ms-text-size-adjust: 100%;
-webkit-text-size-adjust: 100%;
}

/* What is does: Centers email on Android 4.4 */
div[style*="margin: 16px 0"] {
margin:0 !important;
}

/* What it does: Stops Outlook from adding extra spacing to tables. */
table,
td {
mso-table-lspace: 0pt !important;
mso-table-rspace: 0pt !important;
}

/* What it does: Fixes webkit padding issue. Fix for Yahoo mail table alignment bug. Applies table-layout to the first 2 tables then removes for anything nested deeper. */
table {
border-spacing: 0 !important;
border-collapse: collapse !important;
table-layout: fixed !important;
margin: 0 auto !important;
}
table table table {
table-layout: auto; 
}

/* What it does: Uses a better rendering method when resizing images in IE. */
img {
-ms-interpolation-mode:bicubic;
}

/* What it does: A work-around for iOS meddling in triggered links. */
.mobile-link--footer a,
a[x-apple-data-detectors] {
color:inherit !important;
text-decoration: underline !important;
}

</style>

<!-- Progressive Enhancements -->
<style>

/* What it does: Hover styles for buttons */
.button-td,
.button-a {
transition: all 100ms ease-in;
}
 
.button-a:hover {
background: #555555 !important;
border-color: #555555 !important;
}

/* Media Queries */
@media screen and (max-width: 600px) {

.email-container {
width: 100% !important;
margin: auto !important;
}

/* What it does: Forces elements to resize to the full width of their container. Useful for resizing images beyond their max-width. */
.fluid {
max-width: 100% !important;
height: auto !important;
margin-left: auto !important;
margin-right: auto !important;
}

/* What it does: Forces table cells into full-width rows. */
.stack-column,
.stack-column-center {
display: block !important;
width: 100% !important;
max-width: 100% !important;
direction: ltr !important;
}
/* And center justify these ones. */
.stack-column-center {
text-align: center !important;
}

/* What it does: Generic utility class for centering. Useful for images, buttons, and nested tables. */
.center-on-narrow {
text-align: center !important;
display: block !important;
margin-left: auto !important;
margin-right: auto !important;
float: none !important;
}
table.center-on-narrow {
display: inline-block !important;
}

}

</style>

</head> <body bgcolor="#f1f1f1" width="100%" style="margin: 0;">
<center style="width: 100%; background: #f1f1f1;">
';
		return $head;
	}

	function email_inbox_preview() {
		$this->email[] = '<!-- Visually Hidden Preheader Text : BEGIN -->
<div style="display:none;font-size:1px;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;mso-hide:all;font-family: sans-serif;">
(Optional) This text will appear in the inbox preview, but not the email body.
</div>
<!-- Visually Hidden Preheader Text : END -->';
	}
	function email_header($content) {
		
		$this->email[] = '<!-- Email Header : BEGIN -->
<table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: auto;width:100%;" class="email-container">
<tr>
<td style="padding: 20px 0!important; text-align: center;font-size:28px;"><div class="email_title"><div class="editable_content">'.$content['title'].'</div></div>
</td>
</tr>
</table>
<!-- Email Header : END --><table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center"  style="margin: auto;" class="email-container">';
	}

	function email_hero_image($content) {
		$this->email[]  = '<!-- Hero Image, Flush : BEGIN -->
<tr>
<td bgcolor="#ffffff" style="text-align:center;padding-top:45px;width:100%;">
<img src="'.$content['image'].'"  alt="alt_text" border="0" align="center" class="fluid" style="background: #dddddd;font-family:sans-serif;font-size:15px;mso-height-rule:exactly;line-height:20px;color:#555555;max-width:100%;">
</td>
</tr>
<!-- Hero Image, Flush : END -->';
	}

	function email_1_col_text_btn($content) {

		$this->email[]  = '<!-- 1 Column Text : BEGIN -->
<tr>
<td bgcolor="#ffffff" style="padding: 40px; font-size : 18px; text-align: center; font-family: sans-serif; mso-height-rule: exactly; line-height: 28px; color: #555555;" class="email_message">
<div class="email_message_content"><div class="editable_content ecard_editable_content">'.$content['message'].'</div></div>
<br>
</td>
</tr>
<!-- Button : Begin -->
<tr>
<td style="background:#fff; height:76px; vertical-align:top; text-align:center;" class="button-td">
<a href="'.$content['link'].'" style="background: #da1a17; border: 15px solid #da1a17; font-family: sans-serif; font-size: 13px; line-height: 1.1; text-align: center; text-decoration: none; display: inline-block; border-radius: 3px; font-weight: bold;" class="button-a">
&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#ffffff;">'.$content['value'].'</span>&nbsp;&nbsp;&nbsp;&nbsp;
</a>
</td>
</tr>
<!-- Button : END -->
';
	}

	function email_1_col_bg_text($content) {
		$this->email[]  = '<!-- Background Image with Text : BEGIN -->
<tr>
<!-- Bulletproof Background Images c/o https://backgrounds.cm -->
<td background="'.$content['image'].'" bgcolor="#222222" valign="middle" style="text-align: center; background-position: center center !important; background-size: cover !important;">

<!--[if gte mso 9]>
<v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width:600px;height:175px; background-position: center center !important;">
<v:fill type="tile" src="'.$content['image'].'" color="#222222" />
<v:textbox inset="0,0,0,0">
<![endif]-->
<div>
<table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
<td valign="middle" style="text-align: center; padding: 40px; font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #ffffff;">
'.$content['message'].'
</td>
</tr>
</table>
</div>
<!--[if gte mso 9]>
</v:textbox>
</v:rect>
<![endif]-->
</td>
</tr>
<!-- Background Image with Text : END -->';
		
	}

	function email_2_col_img_text($columns) {
		$this->email[]  = '<!-- 2 Even Columns : BEGIN -->
<tr>
<td bgcolor="#ffffff" align="center" valign="top" style="padding: 10px;">
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
<!-- Column : BEGIN -->
<td class="stack-column-center">
<table role="presentation" cellspacing="0" cellpadding="0" border="0">
<tr>
<td style="padding: 10px; text-align: center">
<img src="'.$columns[0]['image'].'" width="270" height="270" alt="alt_text" border="0" class="fluid" style="background: #dddddd; font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555;">
</td>
</tr>
<tr>
<td style="font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555; padding: 0 10px 10px; text-align: left;" class="center-on-narrow">
'.$columns[0]['message'].'</td>
</tr>
</table>
</td>
<!-- Column : END -->
<!-- Column : BEGIN -->
<td class="stack-column-center">
<table role="presentation" cellspacing="0" cellpadding="0" border="0">
<tr>
<td style="padding: 10px; text-align: center">
<img src="'.$columns[1]['image'].'" width="270" height="270" alt="alt_text" border="0" class="fluid" style="background: #dddddd; font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555;">
</td>
</tr>
<tr>
<td style="font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555; padding: 0 10px 10px; text-align: left;" class="center-on-narrow">
'.$columns[1]['message'].'</td>
</tr>
</table>
</td>
<!-- Column : END -->
</tr>
</table>
</td>
</tr>
<!-- 2 Even Columns : END -->';
		
	}

	function email_3_col_img_text($columns) {
		$this->email[]  = '<!-- 3 Even Columns : BEGIN -->
<tr>
<td bgcolor="#ffffff" align="center" valign="top" style="padding: 10px;">
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
<!-- Column : BEGIN -->
<td width="33.33%" class="stack-column-center">
<table role="presentation" cellspacing="0" cellpadding="0" border="0">
<tr>
<td style="padding: 10px; text-align: center">
<img src="'.$columns[0]['image'].'" width="170" height="170" alt="alt_text" border="0" class="fluid" style="background: #dddddd; font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555;">
</td>
</tr>
<tr>
<td style="font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555; padding: 0 10px 10px; text-align: left;" class="center-on-narrow">
'.$columns[0]['message'].'</td>
</tr>
</table>
</td>
<!-- Column : END -->
<!-- Column : BEGIN -->
<td width="33.33%" class="stack-column-center">
<table role="presentation" cellspacing="0" cellpadding="0" border="0">
<tr>
<td style="padding: 10px; text-align: center">
<img src="'.$columns[1]['image'].'" width="170" height="170" alt="alt_text" border="0" class="fluid" style="background: #dddddd; font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555;">
</td>
</tr>
<tr>
<td style="font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555; padding: 0 10px 10px; text-align: left;" class="center-on-narrow">
'.$columns[1]['message'].'</td>
</tr>
</table>
</td>
<!-- Column : END -->
<!-- Column : BEGIN -->
<td width="33.33%" class="stack-column-center">
<table role="presentation" cellspacing="0" cellpadding="0" border="0">
<tr>
<td style="padding: 10px; text-align: center">
<img src="'.$columns[0]['image'].'" width="170" height="170" alt="alt_text" border="0" class="fluid" style="background: #dddddd; font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555;">
</td>
</tr>
<tr>
<td style="font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555; padding: 0 10px 10px; text-align: left;" class="center-on-narrow">
'.$columns[0]['message'].'</td>
</tr>
</table>
</td>
<!-- Column : END -->
</tr>
</table>
</td>
</tr>
<!-- 3 Even Columns : END -->';
		
	}

	function email_thumb_left_text_right($content) {
		$this->email[]  = '            <!-- Thumbnail Left, Text Right : BEGIN -->
<tr>
<td bgcolor="#ffffff" dir="ltr" align="center" valign="top" width="100%" style="padding: 10px;">
<table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
<!-- Column : BEGIN -->
<td width="33.33%" class="stack-column-center">
<table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
<td dir="ltr" valign="top" style="padding: 0 10px;">
<img src="'.$content['image'].'" width="170" height="170" alt="alt_text" border="0" class="center-on-narrow" style="background: #dddddd; font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555;">
</td>
</tr>
</table>
</td>
<!-- Column : END -->
<!-- Column : BEGIN -->
<td width="66.66%" class="stack-column-center">
<table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
<td dir="ltr" valign="top" style="font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555; padding: 10px; text-align: left;" class="center-on-narrow">
<strong style="color:#111111;">'.$content['title'].'</strong>
<br><br>
'.$content['message'].'
<br><br>
<!-- Button : Begin -->
<table role="presentation" cellspacing="0" cellpadding="0" border="0" class="center-on-narrow" style="float:left;">
<tr>
    <td style="border-radius: 3px; background: #222222; text-align: center;" class="button-td">
        <a href="'.$content['link'].'" style="background: #222222; border: 15px solid #222222; font-family: sans-serif; font-size: 13px; line-height: 1.1; text-align: center; text-decoration: none; display: block; border-radius: 3px; font-weight: bold;" class="button-a">
            &nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#ffffff">'.$content['value'].'</span>&nbsp;&nbsp;&nbsp;&nbsp;
        </a>
    </td>
</tr>
</table>
<!-- Button : END -->    
</td>
</tr>
</table>
</td>
<!-- Column : END -->
</tr>
</table>
</td>
</tr>
<!-- Thumbnail Left, Text Right : END -->';
	}

	function email_thumb_right_text_left($content) {
		$this->email[]  = ' <!-- Thumbnail Right, Text Left : BEGIN -->
<tr>
<td bgcolor="#ffffff" dir="rtl" align="center" valign="top" width="100%" style="padding: 10px;">
<table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
<!-- Column : BEGIN -->
<td width="33.33%" class="stack-column-center">
<table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
<td dir="ltr" valign="top" style="padding: 0 10px;">
<img src="'.$content['image'].'" width="170" height="170" alt="alt_text" border="0" class="center-on-narrow" style="background: #dddddd; font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555;">
</td>
</tr>
</table>
</td>
<!-- Column : END -->
<!-- Column : BEGIN -->
<td width="66.66%" class="stack-column-center">
<table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
<td dir="ltr" valign="top" style="font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555; padding: 10px; text-align: left;" class="center-on-narrow">
<strong style="color:#111111;">'.$content['title'].'</strong>
<br><br>
'.$content['message'].'<br><br>
<!-- Button : Begin -->
<table role="presentation" cellspacing="0" cellpadding="0" border="0" class="center-on-narrow" style="float:left;">
<tr>
    <td style="border-radius: 3px; background: #222222; text-align: center;" class="button-td">
        <a href="'.$content['link'].'" style="background: #222222; border: 15px solid #222222; font-family: sans-serif; font-size: 13px; line-height: 1.1; text-align: center; text-decoration: none; display: block; border-radius: 3px; font-weight: bold;" class="button-a">
            &nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#ffffff">'.$content['value'].'</span>&nbsp;&nbsp;&nbsp;&nbsp;
        </a>
    </td>
</tr>
</table>
<!-- Button : END -->    
</td>
</tr>
</table>
</td>
<!-- Column : END -->
</tr>
</table>
</td>
</tr>
<!-- Thumbnail Right, Text Left : END -->';
	}

	function email_space() {
		$this->email[]  = '<tr>
<td height="40" style="font-size: 0; line-height: 0;">
&nbsp;
</td>
</tr>';
		return $email_space;
	}

	function email_1_col_text() {
		$this->email[]  = ' <!-- 1 Column Text + Button : BEGIN -->
<tr>
<td bgcolor="#ffffff">
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
<td style="padding: 40px; font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555;">
Maecenas sed ante pellentesque, posuere leo id, eleifend dolor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Praesent laoreet malesuada cursus. Maecenas scelerisque congue eros eu posuere. Praesent in felis ut velit pretium lobortis rhoncus ut&nbsp;erat.
</td>
</tr>
</table>
</td>
</tr>
<!-- 1 Column Text + Button : BEGIN -->';
		
	}

	function email_content($extra_details) {
		$body = $this->template_header().'<!-- Email Body : BEGIN -->';
        if( is_array( $this->email ) ) {
            $body .= implode('', $this->email);
        }
        $body .='<!-- Email Body : END -->'.$this->template_footer($extra_details['extra_footer']);
        return $body;
	}

	function template_footer($content) {
		$footer = '</table>';
		if( $content != '' ) {
			$footer .= '<!-- Email Footer : BEGIN -->
<table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" width="600" style="margin: auto;" class="email-container">
<tr>
<td style="padding: 40px 10px;width: 100%;font-size: 12px; font-family: sans-serif; mso-height-rule: exactly; line-height:18px; text-align: center; color: #888888;">
<br><br>
'.nl2br($content).'
<br><br> 
</td>
</tr>
</table>
<!-- Email Footer : END -->';
		}
		$footer .='
<!-- Email Body : END -->
</center>
</body>
</html>';
		return $footer;
	}

}

	
}

