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
	$('.it-exchange-abandoned-carts-add-new-email-template').insertAfter('#post-query-submit').css('display', 'inline-block');
});
