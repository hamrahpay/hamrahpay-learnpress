<?php
/**
 * Template for displaying Hamrahpay payment form.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/hamrahpay-payment/form.php.
 *
 * @author   Hamrahpay
 * @package  LearnPress/Hamrahpay/Templates
 * @version  1.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php $settings = LP()->settings; ?>

<p><?php echo $this->get_description(); ?></p>

<div id="learn-press-hamrahpay-form" class="<?php if(is_rtl()) echo ' learn-press-form-hamrahpay-rtl'; ?>">
    <p class="learn-press-form-row">
        <label><?php echo wp_kses( __( 'Email', 'learnpress-hamrahpay' ), array( 'span' => array() ) ); ?></label>
        <input type="text" name="learn-press-hamrahpay[email]" id="learn-press-hamrahpay-payment-email"
               maxlength="19" value=""  placeholder="info@midiyasoft.com"/>
		<div class="learn-press-hamrahpay-form-clear"></div>
    </p>
	<div class="learn-press-hamrahpay-form-clear"></div>
    <p class="learn-press-form-row">
        <label><?php echo wp_kses( __( 'Mobile', 'learnpress-hamrahpay' ), array( 'span' => array() ) ); ?></label>
        <input type="text" name="learn-press-hamrahpay[mobile]" id="learn-press-hamrahpay-payment-mobile" value=""
               placeholder="09121234567"/>
		<div class="learn-press-hamrahpay-form-clear"></div>
    </p>
	<div class="learn-press-hamrahpay-form-clear"></div>
</div>
