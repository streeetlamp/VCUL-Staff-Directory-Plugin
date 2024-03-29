module.exports = function( grunt ) {
	grunt.initConfig( {
		pkg: grunt.file.readJSON( "package.json" ),

		stylelint: {
			src: "src/css/*.css"
		},

		postcss: {
			options: {
				processors: [
					require( "autoprefixer" )( {
						browsers: [ "> 1%", "ie 8-11", "Firefox ESR" ]
					} )
				]
			},
			dist: {
				cwd: "src/css/",
				src: "*.css",
				dest: "css/",
				expand: true
			}
		},

		jscs: {
			scripts: {
				src: [ "Gruntfile.js", "src/js/*.js" ],
				options: {
					preset: "jquery",
					requireCamelCaseOrUpperCaseIdentifiers: false, // We rely on name_name too much to change them all.
					maximumLineLength: 250
				}
			}
		},

		jshint: {
			grunt_script: {
				src: [ "Gruntfile.js" ],
				options: {
					curly: true,
					eqeqeq: true,
					noarg: true,
					quotmark: "double",
					undef: true,
					unused: false,
					node: true     // Define globals available when running in Node.
				}
			},
			directory_script: {
				src: [ "src/js/*.js" ],
				options: {
					bitwise: true,
					curly: true,
					eqeqeq: true,
					forin: true,
					freeze: true,
					noarg: true,
					nonbsp: true,
					quotmark: "double",
					undef: true,
					unused: true,
					browser: true, // Define globals exposed by modern browsers.
					jquery: true   // Define globals exposed by jQuery.
				}
			}
		},

		uglify: {
			dist: {
				src: "src/js/directory.js",
				dest: "js/directory.min.js"
			}
		},

		phpcs: {
			plugin: {
				src: [ "./*.php", "./includes/*.php", "./tests/*.php" ]
			},
			options: {
				bin: "vendor/bin/phpcs --extensions=php --ignore=\"*/vendor/*,*/node_modules/*\"",
				standard: "phpcs.ruleset.xml"
			}
		}

	} );

	grunt.loadNpmTasks( "grunt-contrib-jshint" );
	grunt.loadNpmTasks( "grunt-contrib-uglify" );
	grunt.loadNpmTasks( "grunt-jscs" );
	grunt.loadNpmTasks( "grunt-phpcs" );
	grunt.loadNpmTasks( "grunt-postcss" );
	grunt.loadNpmTasks( "grunt-stylelint" );

	// Default task(s).
	grunt.registerTask( "default", [ "stylelint", "jscs", "jshint", "postcss", "uglify", "phpcs" ] );
};
