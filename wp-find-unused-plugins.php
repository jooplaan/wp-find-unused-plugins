<?php

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Find unused plugins on a multisite network.
	 *
	 * WP CLI command that terates through all sites on a network to find plugins which aren't enabled
	 * on any site. WP CLI needs to be installed for this command to work.
	 * See: https://wp-cli.org/
	 *
	 * Use in bash shell: wp find-unused-plugins
	 */
	$find_unused_plugins_command = function() {
		$response = WP_CLI::launch_self( 'site list', array(), array( 'format' => 'json' ), false, true );
		$sites = json_decode( $response->stdout );
		$unused = array();
		$used = array();
		foreach( $sites as $site ) {
			WP_CLI::log( "Checking {$site->url} for unused plugins..." );
			$response = WP_CLI::launch_self( 'plugin list', array(), array( 'url' => $site->url, 'format' => 'json' ), false, true );
			$plugins = json_decode( $response->stdout );
			foreach( $plugins as $plugin ) {
				if ( 'inactive' == $plugin->status && ! in_array( $plugin->name, $used ) ) {
					$unused[ $plugin->name ] = $plugin;
				} else {
					if ( isset( $unused[ $plugin->name ] ) ) {
						unset( $unused[ $plugin->name ] );
					}
					$used[$plugin->name] = $plugin;
				}
			}
		}

		$count_found = count( $unused );
		if ( $count_found > 0 ) {
			echo "\r\n";
			echo 'Unused plugins found ' . $count_found . "\r\n";
			WP_CLI\Utils\format_items( 'table', $unused, array( 'name', 'version', 'update' ) );
		} else {
			echo "\r\n";
			echo 'All plugins are in use.' . "\r\n";
		}
	};
	WP_CLI::add_command( 'find-unused-plugins', $find_unused_plugins_command, array(
		'before_invoke' => function(){
			if ( ! is_multisite() ) {
				WP_CLI::error( 'This is not a multisite installation.' );
			}
		},
	) );
}