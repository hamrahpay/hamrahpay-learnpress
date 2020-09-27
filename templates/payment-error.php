<?php
/**
 * Template for displaying Hamrahpay payment error message.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/hamrahpay-payment/payment-error.php.
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

<div class="learn-press-message error ">
	<div><?php echo __( 'Transation failed', 'learnpress-hamrahpay' ); ?></div>		
</div>
