/**
 * jquery.multifileselector.js
 *
 * Original code by Rocky Meza
 * Portions are Copyright (c) 2013, Fusionbox, Inc.
 *
 * Updated and modified code by M.E. 
 * 
 * This jQuery file picker plugin allows choosing multiple files, via <input type="file" > fields, one at a time, inserting the file names into HTML view 
 *
 * Ready-made for Bootstrap and FontAwesome styling when listing selected files. Default inline template already has related class names. 
 *
 * This plugin uses 1 input field per file, new fields are added automatically up to the max allowed, if any.
 *
 * When using this plugin do not enable "multiple" on your HTML file input fields. That's not necessary. 
 * 
 * IMPORTANT: Use a class selector to initialize the plugin! DO NOT use an ID selector! See below examples:
 *
 * Example input field: 
 * <input class="file_input" type="file" name="files[]">
 *
 * Example init: $( '.file_input' ).multifileselector({});
 *
 * NOTE: If you're using an image or icon to replace the browser's default "Browse button for the file input field
 * then be sure to use CSS styling of "opacity:0" on your file input fields to ensure they are not visible on the screen. 
 *   
 */
;
( function( $, global, undefined ) {
	
	$.fn.multifileselector = function ( options ) {
		
		var defaults = {
			container : '', // container where to insert list of file names, leave empty to not display selected file names
			template_callback : false, // callback to format the HTML for the list of file names in the container, false for default
			allowed_file_types : false, // allow file types, array, example: [ 'zip', 'jpg', 'gif' ], false for all types
			file_type_not_allowed_message: 'File type not allowed',
			max_file_size : false, // max file size in KB, example: a value 1024 = 1MB, false for no size limit
			max_file_size_error_message: 'File exceeds the size limit of %sKB',
			max_files: false, // max number of file that can added for the selector, false for no limit
			max_file_count_error_message: 'You may only attach %s files',
			auto_hide_fields: true, // Set this to false if you're using an image or icon in place of the browser's default "Browse" button for file input fields, in which case you should probably add CSS styling of "opacity:0" on the input fields to rendering them invisible. 
		}

		$.fn.multifileselector.settings = $.extend( {}, defaults, options );
	
		var container = $.fn.multifileselector.settings.container;
	
		var file_count = 1;

		var $container, 
		add_input = function( event ) {
			
			var file = $.fn.multifileselector.get_file_object( this );

			if ( false !== $.fn.multifileselector.settings.allowed_file_types ) {

				var last_dot = file.name.lastIndexOf( '.' );
				var ext = file.name.substring( last_dot + 1 );

				if ( -1 === $.inArray( ext, $.fn.multifileselector.settings.allowed_file_types ) ) { 
					alert( $.fn.multifileselector.settings.file_type_not_allowed_message );
					return;
				}
			}
			
			var size_ok = $.fn.multifileselector.validate_file_size( file );
			
			if ( !size_ok ) { 
				var error_msg = $.fn.multifileselector.settings.max_file_size_error_message;
				error_msg = error_msg.replace( '%s', $.fn.multifileselector.settings.max_file_size );
				alert( error_msg );
				return;
			}

			if ( false !== $.fn.multifileselector.settings.max_files ) {
				if ( file_count > $.fn.multifileselector.settings.max_files ) { 
					var error_msg = $.fn.multifileselector.settings.max_file_count_error_message;
					error_msg = error_msg.replace( '%s', $.fn.multifileselector.settings.max_files );
					alert( error_msg );
					return; 
				}
			}
				
			var $this = $( this ),
				new_input = $this.clone( true, false );

			// newer versions of Firefox don't clear this on clone
			new_input[0].value = '';

			$this
				.unbind(event)
				//.hide() // don't hide the inputs, developers should use CSS opacity:0 in their inputs instead 
				// due to browser security concerns
				.after( new_input );

			if ( true == $.fn.multifileselector.settings.auto_hide_fields ) { 
				$this.hide();
			}
			
			if ( 'undefined' == typeof $container || '' == $container || 0 == $container.length ) { 
				return;
			};
			
			template_callback = $.fn.multifileselector.settings.template_callback || $.fn.multifileselector.template_callback;

			template_callback( file )
				.appendTo( $container )
				.find( '.multifile_remove_input' )
				.bind( 'click.multifile', bind_remove_input( $this ) );
				
			file_count++;
		},
		bind_remove_input = function( $input ) {
			// TODO: make this customizable
			return function( event ) {
				$input.remove();
				$( this ).parents( '.file_attachment' ).remove();
				event.preventDefault();
			};
		};

		if ( container ) {
			if ( typeof container == 'string' )
				$container = $(container);
			else
				$container = container;
		} else {
			$container = $( '<div class="multifileselector_container" />' );
			this.after($container);
		}

		return this.each( function( index, elem ) {
			$(this).unbind().bind('change.multifileselector', add_input);
		});
	};

	$.fn.multifileselector.template_callback = function( file ) {
		return $(
			'<li class="file_attachment multiple_emails-email">' +
				'<a href="#" class="multifile_remove_input">X </a>' +
				'<span class="filename"></span>' +
			'</li>')
			.append( $('<span>' ).attr( 'class', 'filename' ).text( file.name ) );
	};

	$.fn.multifileselector.validate_file_size = function( file  ) {

		if ( false == this.settings.max_file_size ) {
			return true;
		};
		
		size = Math.round( ( file.size / 1024 ) ); 

		if ( size > this.settings.max_file_size ) { 
			return false;
		} else { 
			return true;
		};
	};
 
	$.fn.multifileselector.get_file_object = function( input ) {
		var file = {};
		
		// check for HTML5 FileList support
		if ( !!global.FileList ) {
			if ( input.files.length == 1 ) {
				file = input.files[0];
			} else {
				file._multiple = true;

				// We do this in order to support `multiple` files.
				// You can't display them separately because they 
				// belong to only one file input.  It is impossible
				// to remove just one of the files.
				file.name = input.files[0].name;
				
				for ( var i = 1, _len = input.files.length; i < _len; i++ ) {
					file.name += ', ' + input.files[i].name;
				}
			}
		} else {
			file._html4 = true;
			file.name = input.value;
		}

		return file;
	};
})( jQuery, this );