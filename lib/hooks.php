<?php
/** 
 * Whenever a cart is cached, confirm it has products and add, update, or delete its status
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_abandoned_carts_bump_active_shopper( $customer, $cart_data ) {
	// If user deleted cart, remove them as a qualified shopper
	if ( empty( $cart_data['products'] ) ) {
		it_exchange_abandoned_carts_delete_last_qualified_activity_for_user( $customer->id );
		return;
	}

	// Update their time stamp as a qualified shopper
	it_exchange_abandoned_carts_update_last_qualified_activity_for_user( $customer->id );
}
add_action( 'it_exchange_cache_customer_cart', 'it_exchange_abandoned_carts_bump_active_shopper', 10, 2 );

/**
 * Register scripts
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_abandoned_carts_register_scripts( $hook ) {
	$screen = get_current_screen();

	// If we're on the screen, enqueue our scripts. It is registered by Exchange in the admin
	if ( ! empty( $screen->id ) && 'edit-it_ex_abandoned' == $screen->id ) {
		// ChartJS
		wp_enqueue_script( 'ithemes-chartjs' );
	}

}
add_action( 'admin_print_scripts', 'it_exchange_abandoned_carts_register_scripts' );

/**
 * Add content to top of Abanded Carts Admin Page
 *
 * @since 1.0.0
 *
 * @return object
*/
function it_exchange_abdandoned_carts_insert_custom_dashboard( $incoming_from_wp_filter ) {
	?>
	<style type="text/css">
		.it-exchange-abandoned-carts-dashboard .abandoned-carts-overview { background:#fff;padding:20px; }
		.it-exchange-abandoned-carts-dashboard .abandoned-carts-overview h3 { margin-top:0; }
		.it-exchange-abandoned-carts-dashboard .abandoned-carts-overview .overview-item { width:20%; padding:5px; margin:5px; float:left;text-align:center; }
		.it-exchange-abandoned-carts-dashboard .abandoned-carts-overview .overview-item .overview-item-value { font-weight:bold;font-size:1.2em; }
	</style>
	<script type="text/javascript">
		jQuery( document ).ready(function( $ ) {
			//Get context with jQuery - using jQuery's .get() method.
			var itExchangeAbandonedCartOverviewCTX = $("#it-exchange-abandoned-cart-overview-chart").get(0).getContext("2d");
			//This will get the first returned node in the jQuery collection.
			var exampleData = {
				labels: ["2014-06-23","2014-06-24","2014-06-25","2014-06-26","2014-06-27","2014-06-28","2014-06-29"],
				datasets: [
					{
						fillColor : "rgba(151,187,205,0.5)",
						strokeColor : "rgba(151,187,205,1)",
						pointColor : "rgba(151,187,205,1)",
						pointStrokeColor : "#fff",
						data : [28,48,40,19,96,27,100]
					}
				]
			};
			var itExchangeAbandonedCartOverview = new Chart(itExchangeAbandonedCartOverviewCTX).Line(exampleData);
		});
	</script>
	<div class="it-exchange-abandoned-carts-dashboard">
		<div class="abandoned-carts-overview">
			<h3><?php _e( 'Overview', 'LION' ); ?></h3>
			<div class="overview-item overview-item-recovered-carts">
				<div class="overview-item-value">27</div>
				<div class="overview-item-title">Recovered Carts</div>
			</div>
			<div class="overview-item overview-item-recovered-revenue">
				<div class="overview-item-value">$4,360.20</div>
				<div class="overview-item-title">Recovered Revenue</div>
			</div>
			<div class="overview-item overview-item-conversion">
				<div class="overview-item-value">15%</div>
				<div class="overview-item-title">Recovered Revenue</div>
			</div>
			<div class="overview-item overview-item-average-value">
				<div class="overview-item-value">$161.48</div>
				<div class="overview-item-title">Average Recovered Value</div>
			</div>
			<div class="overview-chart clear">
				<h3><?php _e( 'Recovered Carts', 'LION' ); ?></h3>
				<canvas id="it-exchange-abandoned-cart-overview-chart" width="900" height="200"></canvas>
			</div>
		</div>
		<div class="abdandoned-carts-nav">
			<h3 class="nav-tab-wrapper">
			<a class="nav-tab nav-tab-active" href="<?php echo admin_url( 'edit.php?post_type=it_ex_abandoned' ); ?>"><?php _e( 'Carts', 'LION' ); ?></a>
			<a class="nav-tab" href="<?php echo admin_url( 'edit.php?post_type=it_ex_abandond_email' ); ?>"><?php _e( 'Emails', 'LION' ); ?></a>
			<a class="nav-tab" href="<?php echo admin_url( 'admin.php?page=it-exchange-abandoned-email-settings' ); ?>"><?php _e( 'Settings', 'LION' ); ?></a>
			</h3>
		</div>

	</div>
	<?php
	return $incoming_from_wp_filter;
}
add_filter( 'views_edit-it_ex_abandoned', 'it_exchange_abdandoned_carts_insert_custom_dashboard' );
