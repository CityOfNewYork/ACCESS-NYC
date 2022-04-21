<?php

namespace Wpae\Reviews;

class ReviewsUI
{

    private $reviewLogic;

    public function __construct()
    {
        $this->reviewLogic = new ReviewLogic();
    }

    public function render()
    {

        if($this->reviewLogic->shouldShowReviewModal()) {

            ?>
            <style type="text/css">
                .wpallexport-plugin .wpae-reviews-notice {
                    margin-top: 40px;
                    padding-top: 18px;
                    padding-bottom: 22px;
                }

                .wpae-reviews-notice h1 {
                    color: #435F9A;
                    font-size: 1.4em;
                    font-weight: 500;
                    padding: 0;
                }

                .wpae-buttons-container {
                    margin-top: 10px;
                }

                .wpae-reviews-notice .wpae-buttons-container button, #wpae-feedback button {
                    padding: 6px 10px;
                    margin-right: 9px;
                    position: relative;
                    text-decoration: none;
                    border: 1px solid #435F9A;
                    border-radius: 2px;
                    text-shadow: none;
                    font-weight: 500;
                    font-size: 1.1em;
                    line-height: normal;
                    color: #435F9A;
                    cursor: pointer;
                    background-color: white;
                }

                .wpae-reviews-notice .wpae-buttons-container button:hover, #wpae-feedback button:hover {
                    background: #f0f0f1;
                    border-color: #0a4b78;
                    color: #0a4b78;
                                    }
                .wpae-reviews-notice .wpae-buttons-container button:focus, #wpae-feedback button:focus {
                    border: 1px solid rgba(0, 0, 0, 0.5);
                    outline: none;
                }

                .wpae-reviews-notice button:hover {
                    background-color: #FAFAFA;
                }

                #wpae-review {
                    display: none;
                    justify-content: flex-start;
                    align-items: baseline;
                }

                #wpae-review p, #wpae-feedback p {
                    display: block;
                    font-size: 1.1em;
                }

                #wpae-review .wpae-buttons-container {
                    justify-content: flex-start;

                }

                #wpae-feedback {
                    display: none;
                    justify-content: flex-start;
                    align-items: baseline;
                }

                #wpae-feedback textarea {
                    width: 100%;
                    height: 100px;
                }

                #wpae-feedback .wpae-submit-feedback {
                    display: flex;
                    flex-direction: row;
                    align-items: center;
                    margin-top: 10px;
                }

                #wpae-feedback .wpae-submit-feedback button {
                    margin-right: 10px;
                }

                .wpae-reviews-notice .notice-dismiss {
                    position: relative;
                    float: right;
                    top: -15px;
                    right: -10px;
                }
                .wpae-reviews-notice .notice-dismiss:focus {
                    border: none;
                    box-shadow: none;
                }


                .wpae-submit-confirmation {
                    padding-top: 20px;
                    padding-bottom: 20px;
                    display: none;
                }
            </style>
            <script type="text/javascript">
                jQuery(document).ready(function () {
                    jQuery('.wpae-review-buttons button').click(function (e) {

                        e.preventDefault();
                        var val = jQuery(this).data('review');

                        if (val === 'good') {
                            jQuery('#wpae-ask-for-review').fadeOut(function () {
                                jQuery('#wpae-review').fadeIn();
                            });
                        } else {
                            jQuery('#wpae-ask-for-review').fadeOut(function () {
                                jQuery('#wpae-feedback').fadeIn();
                            });
                        }

                        return false;
                    });

                    jQuery('.wpae-reviews-notice .notice-dismiss').click(function(e){

                        e.preventDefault();
                        e.stopImmediatePropagation();
                        var request = {
                            action: 'dismiss_review_modal',
                            security: wp_all_export_security,
                            modal_type: jQuery('#wpae-modal-type').val()
                        };

                        jQuery.ajax({
                            type: 'POST',
                            url: '<?php echo admin_url( "admin-ajax.php" ); ?>',
                            data: request,
                            success: function(response) {},
                            dataType: "json"
                        });

                        jQuery('.wpae-reviews-notice').slideUp();
                    });

                    jQuery('.review-link').click(function(){

                        var request = {
                            action: 'dismiss_review_modal',
                            security: wp_all_export_security,
                            modal_type: jQuery('#wpae-modal-type').val()
                        };

                        jQuery.ajax({
                            type: 'POST',
                            url: '<?php echo admin_url( "admin-ajax.php" ); ?>',
                            data: request,
                            success: function(response) {},
                            dataType: "json"
                        });

                        jQuery('.wpae-reviews-notice').slideUp();

                    });

                    jQuery('.wpae-submit-feedback button').click(function(){

                        jQuery(this).prop("disabled", true);

                        var request = {
                            action: 'send_feedback',
                            modal_type: jQuery('#wpae-modal-type').val(),
                            security: wp_all_export_security,
                            plugin: jQuery('#wpae-modal-type').val(),
                            message: jQuery('#wpae-feedback-message').val()
                        };

                        jQuery.ajax({
                            type: 'POST',
                            url: '<?php echo admin_url( "admin-ajax.php" ); ?>',
                            data: request,
                            success: function(response) {
                                jQuery('.wpae-submit-confirmation').show();
                                jQuery('.wpae-review-form').hide();

                            },
                            dataType: "json"
                        });

                    });
                });
            </script>
            <input type="hidden" id="wpae-modal-type" value="<?php esc_attr_e($this->reviewLogic->getModalType()) ;?>" />
            <div style="" class="notice notice-info wpae-reviews-notice">
                <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
                <div id="wpae-ask-for-review">
                    <h1><?php printf(esc_html_e($this->reviewLogic->getModalText()), 'wp-all-export-plugin'); ?></h1>

                    <div class="wpae-buttons-container wpae-review-buttons">
                        <button data-review="good"><?php esc_html_e('Good', 'wp-all-export-plugin'); ?></button>
                        <button data-review="ok"><?php esc_html_e('Just Ok', 'wp-all-export-plugin'); ?></button>
                        <button data-review="bad"><?php esc_html_e('Bad', 'wp-all-export-plugin'); ?></button>
                    </div>
                </div>
                <div id="wpae-review">
                    <h1><?php esc_html_e('That is great to hear, thank you for the feedback!', 'wp-all-export-plugin'); ?></h1>
                    <p>    
                        <?php esc_html_e("Would you be willing to do us a small favor? Unhappy customers are quick to publicly complain, but happy customers rarely speak up and share their good experiences.", 'wp-all-export-plugin'); ?>
                        </br>
                        <?php esc_html_e("If you have a moment, we would love for you to review our add-on in the WordPress.org plugin repository.", 'wp-all-export-plugin'); ?>
                    </p>
                    <div class="wpae-buttons-container">
                        <a class="review-link" href="<?php echo esc_attr($this->reviewLogic->getReviewLink()); ?>" target="_blank">
                            <button><?php printf(esc_html__('Review the %s', 'wp-all-export-plugin'), $this->reviewLogic->getPluginName() ); ?></button>
                        </a>
                    </div>
                </div>
                <div id="wpae-feedback">
                    <div class="wpae-review-form">
                        <h1><?php esc_html_e('Thank you for your feedback, it really helps us improve our products.', 'wp-all-export-plugin'); ?></h1>
                        <p><?php esc_html_e('If you could improve one thing about WP All Export, what would it be?', 'wp-all-export-plugin'); ?></p>
                        <textarea id="wpae-feedback-message"></textarea>
                        <div class="wpae-submit-feedback">
                            <button><?php esc_html_e('Submit', 'wp-all-export-plugin'); ?></button>
                        </div>
                    </div>
                    <div class="wpae-submit-confirmation">
                        Thank you for your feedback. Your message was emailed to support@wpallimport.com from <?php echo get_option('admin_email'); ?>. If you do not receive a confirmation email, it means we didn't receive your message for some reason.
                    </div>

                </div>
            </div>
            <?php
        }
    }

}