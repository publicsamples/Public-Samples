/**
 * Grunt task runners for AT Skins.
 * http://gruntjs.com/
 */

'use strict';

module.exports = function(grunt) {

	grunt.initConfig({

    pkg: grunt.file.readJSON('package.json'),

    sass: {
      skin: {
        files: [{
          expand: true,
          cwd: 'styles/scss',
          src: ['*.scss'],
          dest: 'styles/css',
          ext: '.css'
        }],
        options: {
          precision: 5,
          outputStyle: 'expanded',
          imagePath: "../css/images",
          sourceMap: true
        }
      }
    },

    postcss: {
      skin: {
        src: 'styles/css/**.css',
        options: {
          map: {
            inline: false,
            annotation: 'styles/css'
          },
          processors: [
            require('autoprefixer')({browsers: 'last 4 versions'})
          ]
        }
      }
    },

    csslint: {
      options: {
        csslintrc: '.csslintrc'
      },
      strict: {
        options: {
          import: 2
        },
        src: ['styles/css/**.css']
      }
    },

    watch: {
      skin: {
        files: 'styles/scss/*.scss',
        tasks: ['sass:skin', 'postcss:skin'],
        options: {
        //  livereload: 35729
        }
      }

		}
	});

  grunt.loadNpmTasks('grunt-postcss');
  grunt.loadNpmTasks('grunt-sass');
  grunt.loadNpmTasks('grunt-contrib-csslint');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-browser-sync');

  grunt.registerTask('default', ['watch:skin']);
};
