module.exports = function( grunt ) {

	grunt.initConfig({
		wp_deploy: {
			deploy: {
				options: {
					plugin_slug: 'hm-content-toc',
					svn_user: 'dashaluna',
					build_dir: 'build',  //relative path to your build directory
					assets_dir: 'assets' //relative path to your assets directory (optional).
				},
			}
		},
	})

	grunt.loadNpmTasks( 'grunt-wp-deploy' );

};
