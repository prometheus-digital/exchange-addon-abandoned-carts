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
		wp_enqueue_style( 'it-exchange-abandoned-carts-admin', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/css/admin.css' );
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
	<script type="text/javascript">
		jQuery( document ).ready(function( $ ) {

			var exampleData = {
				labels: ["2014-06-23","2014-06-24","2014-06-25","2014-06-26","2014-06-27","2014-06-28","2014-06-29"],
				datasets: [
					{
						fillColor : "#d1ebb0",
						strokeColor : "#89c43d",
						pointColor : "#89c43d",
						pointStrokeColor : "#fff",
						data : [28,48,40,19,96,27,100]
					}
				]
			};

			// Run function to build the initial chart
			itExchangeAbandonedCartSetupChart(exampleData);

			// Run setup function again on window resize since there is no native redraw method
			jQuery(window).resize( function() {
				itExchangeAbandonedCartSetupChart(exampleData);
			});

		});

		function itExchangeAbandonedCartSetupChart(exampleData) {
			// Get width of canvas parent
			var itExchangeAbandonedCartCanvas = jQuery("#it-exchange-abandoned-cart-overview-chart");
			var itExchangeAbandonedCartChartWidth = itExchangeAbandonedCartCanvas.parent().width();

			// Set width attr to canvas element
			itExchangeAbandonedCartCanvas.attr({
				width: itExchangeAbandonedCartChartWidth,
				height: 250
			});

			// Set chart options
			var options = {
				scaleFontFamily : "'Open Sans'",
				scaleFontStyle : "bold",
				scaleFontSize : 14,
				scaleLineColor : "#999",
				scaleGridLineWidth : 2
			}

			// Draw the chart
			var itExchangeAbandonedCartOverviewCTX = itExchangeAbandonedCartCanvas.get(0).getContext("2d");
			var itExchangeAbandonedCartOverview = new Chart(itExchangeAbandonedCartOverviewCTX).Line(exampleData, options);
		}

	</script>
	<div class="it-exchange-abandoned-carts-dashboard">
		<div class="abandoned-carts-overview">
			<div class="abandoned-carts-overview-items">
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
			</div>
			<div class="overview-chart clear">
				<h3><?php _e( 'Recovered Carts', 'LION' ); ?></h3>
				<canvas id="it-exchange-abandoned-cart-overview-chart"></canvas>
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
