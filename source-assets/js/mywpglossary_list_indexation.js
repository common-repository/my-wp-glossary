
(function ($) {
	'use strict';
	$(window).load(function () {

		function mywpglossary_index(){
			var r = [];
			jQuery.ajax( {
				url        : mywpglossary_admin.rest_url+'mywpglossary/v1/index/',
				method     : 'GET',
				async      : false,
				beforeSend : function (xhr){
					xhr.setRequestHeader( 'X-WP-Nonce', mywpglossary_admin.nonce );
				},
				success    : function( result ){
					alert( result.message );
					//console.log( result );
				},
				error      : function( result ) {
					//console.log( result );
				}
			} );
			return r;
		}

		$( document ).ready(function(){
			$('#mywpglossary-index').click( function( e ){
				e.preventDefault();
				mywpglossary_index();
			});
		});

	});
})(jQuery);
