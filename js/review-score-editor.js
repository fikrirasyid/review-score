(function($){
    $(document).ready(function(){
        /**
         * Add new review aspect
         */             
        $('#add-review-aspect').click(function(e){
            e.preventDefault();
            add_new_review_aspect();
        });

        $('#new-review-aspect').keypress(function(e){
            if( e.keyCode == 13 ){
                e.preventDefault();
                add_new_review_aspect();
            }
        });

        function add_new_review_aspect(){
            var aspect_label = $('#new-review-aspect').val();
            var template = $('#template-aspect').html();
            var new_aspect = template.format( aspect_label, '_review_score_label_' + aspect_label );
            $( '#review-score tbody' ).append( new_aspect );
            $('#new-review-aspect').val('');
        }

        String.prototype.format = function() {
          var args = arguments;
          return this.replace(/{(\d+)}/g, function(match, number) { 
            return typeof args[number] != 'undefined'
              ? args[number]
              : match
            ;
          });
        };        
    })
})(jQuery)