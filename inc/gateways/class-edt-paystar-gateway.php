<?php

/**
 * PayStar Gateway for Easy Donations
 * 
 */

add_action( 'plugins_loaded', 'run_edt_ps_gateway' );

function run_edt_ps_gateway()
{
	if( ! class_exists( 'EDT_paystar_Gateway' ) && class_exists( 'Easy_Donations_Gateway' ) )
	{
		class EDT_paystar_Gateway extends Easy_Donations_Gateway
		{

			const version = '1.0';

			public function __construct()
			{
			}

			public function gateway_settings_fields()
			{
				$gateway_id = 'edt_ps_gateway';
				$gtw_data = edt_ins()->options->get_option( $gateway_id );
				$setting_fields = array(array(
						'id'      => 'paystar_terminal',
						'name'    => '[paystar_terminal]',
						'type'    => 'text',
						'text'    => __('Please Enter PayStar Terminal', EDT_TEXT_DOMAIN),
						'value'   => ( isset( $gtw_data['paystar_terminal'] ) ) ? $gtw_data['paystar_terminal'] : ''
					));
				$this->add_gtw_setting( $gateway_id, __('PayStar', EDT_TEXT_DOMAIN), $setting_fields );
			}

			public function before_send( $payment )
			{
				echo '<div class="paystar-wc-wait" style="position:fixed; width:100%; height:100%; left:0; top:0; z-index:9999; opacity:0.90; -moz-opacity:0.90; filter:alpha(opacity=90); background-color:#fff;">
						<img src="' . esc_url(EASYDONATIONS_PLUGIN_URL) . 'assets/img/wait.gif" style="position:fixed; left:50%; top:50%; width:466px; height:368px; margin:-184px 0 0 -233px;" />
					</div>';
				$gtw_data = edt_ins()->options->get_option( 'edt_ps_gateway' );
				$active_currency = edt_ins()->options->get_option( 'donate_form_active_currency' );
				if( $active_currency['Code'] == 'IRT' ) $payment['amount'] = $payment['amount'] * 10;
				require_once(dirname(__FILE__) . '/paystar_payment_helper.class.php');
				$p = new PayStar_Payment_Helper($gtw_data['paystar_terminal']);
				$r = $p->paymentRequest(array(
						'amount'   => intval(ceil($payment['amount'])),
						'order_id' => $payment['id'],
						'callback' => add_query_arg( array( 'listener' => 'paystar-easy-donations', 'pay_id' => $payment['id'], 'shf_key' => session_id() ), get_site_url().'/' )
					));
				if ($r)
				{
					$_SESSION['paystar_edt_id'] = $payment['id'];
					session_write_close();
					echo '<form name="frmPayStarPayment" method="post" action="https://core.paystar.ir/api/pardakht/payment"><input type="hidden" name="token" value="'.esc_html($p->data->token).'" />';
					echo '<input class="paystar_btn btn button" type="submit" value="'.__('Pay', EDT_TEXT_DOMAIN).'" /></form>';
					echo '<script>document.frmPayStarPayment.submit();</script>';
				}
				else
				{
					$this->add_message( __('Error', EDT_TEXT_DOMAIN) . ' : ' . $p->error, 'error' );
					edt_ins()->payment->complete_payment( $payment, 'failed' );
					header( "location: " . $payment['pay_url'] );        
				}
				die();
			}

			public function on_return( $payment, $post )
			{
				$post_status = sanitize_text_field($_POST['status']);
				$post_order_id = sanitize_text_field($_POST['order_id']);
				$post_ref_num = sanitize_text_field($_POST['ref_num']);
				$post_tracking_code = sanitize_text_field($_POST['tracking_code']);
				$amount = $payment['amount'];

				$gtw_data = edt_ins()->options->get_option( 'edt_ps_gateway' );
				$active_currency = edt_ins()->options->get_option( 'donate_form_active_currency' );
				if( $active_currency['Code'] == 'IRT' ) $amount = $amount * 10;
				require_once(dirname(__FILE__) . '/paystar_payment_helper.class.php');
				$p = new PayStar_Payment_Helper($gtw_data['paystar_terminal']);
				$r = $p->paymentVerify($x = array(
						'status' => $post_status,
						'order_id' => $post_order_id,
						'ref_num' => $post_ref_num,
						'tracking_code' => $post_tracking_code,
						'amount' => intval(ceil($payment['amount'])),
					));
				if ($r)
				{
					$this->add_message( __('Payment Completed. RefNum', EDT_TEXT_DOMAIN) . ' : '.$p->txn_id, 'updated' );
					edt_ins()->payment->complete_payment( $payment, 'completed', $p->txn_id );
				}
				else
				{
					$this->add_message( __('Error', EDT_TEXT_DOMAIN) . ' ( '.$p->error.' )', 'error' );
					edt_ins()->payment->complete_payment( $payment, 'failed' );
				}
				header( "location: " . $payment['pay_url'] );
				die();
			}

		}
		edt_ins()->gateways->register_gateway( 'edt_ps_gateway', __('PayStar', EDT_TEXT_DOMAIN), 'EDT_paystar_Gateway' );
	}

}
