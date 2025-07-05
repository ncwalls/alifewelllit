(function() {
	tinymce.create('tinymce.plugins.Msweditor', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init : function(ed, url) {
			ed.addButton('msw_email', {
				title : 'Add Email Shortcode',
				cmd : 'msw_email',
				image : url + '/email.png'
			});
 
			ed.addButton('msw_button', {
				title : 'Add Button Shortcode',
				cmd : 'msw_button',
				image : url + '/button.png'
			});

			ed.addCommand('msw_email', function() {
				var selected_text = ed.selection.getContent();
				var shortcode;
				if (selected_text) {
					shortcode = '[protected_email email="' + selected_text + '"]';
				} else {
					var email = prompt("Enter Email Address:");
					if (email !== null) {
						shortcode = '[protected_email email="' + email + '"]';
					}
				}
				ed.execCommand('mceInsertContent', 0, shortcode);
			});

			ed.addCommand('msw_button', function() {
				var selected_text = ed.selection.getContent();
				var shortcode;
				var link;
				var target;
				var external;
				if (selected_text) {
					link = prompt("Enter Link URL:");
					external = window.confirm('Want button to open a new window?');
					if (external) {
						target = "_blank";
					} else {
						target = "_self";
					}
					if (link !== null) {
						shortcode = '[makespace_button label="' + selected_text + '" link="' + link + '" target="' + target + '"]';
					}
				} else {
					link = prompt("Enter Link URL:");
					var text = prompt("Enter Button Label:");
					external = window.confirm('Want button to open a new window?');
					if (external) {
						target = "_blank";
					} else {
						target = "_self";
					}
					if (link !== null && text !== null) {
						shortcode = '[makespace_button label="' + text + '" link="' + link + '" target="' + target + '"]';
					}
				}
				ed.execCommand('mceInsertContent', 0, shortcode);
			});
		},
		/**
		 * Creates control instances based in the incomming name. This method is normally not
		 * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
		 * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
		 * method can be used to create those.
		 *
		 * @param {String} n Name of the control to create.
		 * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
		 * @return {tinymce.ui.Control} New control instance or null if no control was created.
		 */
		createControl : function(n, cm) {
			return null;
		},
 
		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @return {Object} Name/value array containing information about the plugin.
		 */
		getInfo : function() {
			return {
				longname : 'Makespace Editor Buttons',
				author : 'Kurt Johnson',
				authorurl : 'http://www.makespaceweb.com',
				infourl : 'http://www.makespaceweb.com',
				version : "0.1"
			};
		}
	});
	// Register plugin
	tinymce.PluginManager.add( 'msweditor', tinymce.plugins.Msweditor );
})();