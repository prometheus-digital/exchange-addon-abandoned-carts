jQuery( document ).ready(function( $ ) {

	var ITExchangeStatsResults = {};

	$.post(ajaxurl, { action: 'ithemes_exchange_abandoned_carts_data', iteac_stat: 'carts' }, function(result) {
		// Run function to build the initial chart
		result = $.parseJSON(result);
		ITExchangeStatsResults = result;
		if ( ITExchangeStatsResults.labels.length > 0 ) {
			itExchangeAbandonedCartSetupChart(result);
		} else {
			$('.no-recovered-carts').show();
		}
	});


	// Run setup function again on window resize since there is no native redraw method
	jQuery(window).resize( function() {
		if ( ITExchangeStatsResults.labels.length > 0 ) {
			itExchangeAbandonedCartSetupChart(ITExchangeStatsResults);
		}
	});

	function itExchangeAbandonedCartSetupChart(ITExchangeStatsResults) {
		// Get width of canvas parent
		var itExchangeAbandonedCartCanvas = jQuery("#it-exchange-abandoned-cart-overview-chart");
		var itExchangeAbandonedCartChartWidth = itExchangeAbandonedCartCanvas.parent().width();

		// Set width attr to canvas element
		itExchangeAbandonedCartCanvas.attr({
			width: itExchangeAbandonedCartChartWidth,
			height: 250
		});

		var itemCount = ITExchangeStatsResults.labels.length;
		var highestAmount = Math.max.apply(Math, ITExchangeStatsResults.datasets[0].data);

		// Set chart options
		var options = {
			scaleFontFamily : "'Open Sans'",
			scaleFontStyle : "bold",
			scaleFontSize : 14,
			scaleLineColor : "#999",
			scaleGridLineWidth : 2,
			scaleOverride: true,
			scaleSteps: highestAmount + 1,
			scaleStepWidth: 1,
			scalseStartValue: 0
		}

		// Draw the chart
		var itExchangeAbandonedCartOverviewCTX = itExchangeAbandonedCartCanvas.get(0).getContext("2d");
		if ( itemCount < 2 ) {
			var itExchangeAbandonedCartOverview = new Chart(itExchangeAbandonedCartOverviewCTX).Bar(ITExchangeStatsResults, options);
		} else {
			var itExchangeAbandonedCartOverview = new Chart(itExchangeAbandonedCartOverviewCTX).Line(ITExchangeStatsResults, options);
		}
	}
	$('.it-exchange-abandoned-carts-add-new-email-template').insertAfter('#post-query-submit').css('display', 'inline-block');
});
