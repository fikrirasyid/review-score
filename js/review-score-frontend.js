jQuery(document).ready(function($) { 
	// Auto check radio box
	$('#comments').on( 'click', '.review-score-star', function(){
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

	// Update the bar based on radio box value
	$('#comments').on( 'change', '#respond .review-score-item-select input[type="radio"]', function(){
		var input = $(this);
		var value = input.val();
		var wrap = input.parents('.review-score-item');

		// Change the bar styling
		wrap.find('.review-score-star').removeClass('starred');
		wrap.find('.review-score-star:nth-child('+value+')').addClass('starred');
		wrap.find('.review-score-star:nth-child('+value+')').prevAll().addClass('starred');

		// Change preview score value
		wrap.find('.review-score-item-score').text(value);		
	});
});
