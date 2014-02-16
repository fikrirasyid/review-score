jQuery(document).ready(function($) { 
	// Auto check radio box
	$('.review-score-star').bind( 'mouseenter', function(){
		var select = $(this);
		var value = parseInt( select.text() );
		var wrap = select.parents('.review-score-item');

		// Change the check stat
		$(wrap).find('input[type="radio"]').prop( 'checked', false );
		$(wrap).find('input[type="radio"][value="'+value+'"]').prop( 'checked', true );

		// Change the bar styling
		select.siblings('.review-score-star').removeClass('starred');
		select.addClass('starred');
		select.prevAll().addClass('starred');

		// Change preview score value
		wrap.find('.review-score-item-score').text(value);
	});
});
