( function( $ ) {

	$( document ).ready( function() {

		if ( typeof ctc_plugin === 'undefined' ) {
			return;
		}

		if ( !$( ctc_plugin ).length ) {
			return;
		}

		if ( ctc_plugin.remove_storage ) {
			localStorage.removeItem( "ctc_run_delete_dialog" );
		}

		$( "input[name='custom_taxonomy_cleanup']" ).on( "click", function( e ) {

			if ( ( 'localStorage' in window ) && ( window[ 'localStorage' ] !== null ) ) {

				var taxonomy = $( "#ctc_taxonomy" ).val();
				var storage = localStorage.getItem( "ctc_run_delete_dialog" );

				if ( taxonomy.length && ( taxonomy !== storage ) ) {

					// Remove confirm dialog after first delete terms dialog.
					localStorage.setItem( "ctc_run_delete_dialog", taxonomy );

					var confirm_msg = ctc_plugin.confirm.replace( /%s/g, taxonomy );

					return confirm( confirm_msg );
				}
			}

		} );
	} );

} )( jQuery );