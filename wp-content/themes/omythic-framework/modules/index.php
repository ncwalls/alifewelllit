<?php

class MakespaceFrameworkModulesMain {
	private $modules;

	function __construct(){
		// We could load anything in here dynamically with a file iterator,
		// but that could be dangerous if a client's site got hacked.
		// Instead, because these don't change much, we'll explicitly load
		// the modules one by one.

		$this->modules = array(
			'case-studies',
			'locations',
			'staff',
			'events'
		);

		foreach( $this->modules as $module ){
			include_once( get_template_directory() . '/modules/' . $module . '/index.php' );
		}

		add_action( 'after_setup_theme', array( $this, 'maybe_move_module_files_to_child_theme' ), 20 );
	}

	function maybe_move_module_files_to_child_theme(){
		$makespace_theme_config = get_option( 'makespace_theme_config', array() );
		if( array_key_exists( 'did_install_framework_templates', $makespace_theme_config ) ){
			foreach( $this->modules as $module ){
				if( current_theme_supports( $module . '-module' ) && !in_array( $module, $makespace_theme_config[ 'did_install_framework_templates' ] ) ){
					$module_template_slug = str_replace( '-', '_', $module );
					$templates = array(
						'archive-' . $module_template_slug . '_module.php',
						'single-' . $module_template_slug . '_module.php'
					);
					foreach( $templates as $template ){
						if( file_exists( get_template_directory() . '/' . $template ) && !file_exists( get_stylesheet_directory() . '/' . $template ) ){
							copy( get_template_directory() . '/' . $template, get_stylesheet_directory() . '/' . $template );
						}
					}
					$makespace_theme_config[ 'did_install_framework_templates' ][] = $module;
				}
			}
		}
		update_option( 'makespace_theme_config', $makespace_theme_config );
	}

}

new MakespaceFrameworkModulesMain();