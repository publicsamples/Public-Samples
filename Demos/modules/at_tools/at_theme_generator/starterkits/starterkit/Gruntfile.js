/**
 * Grunt task runners for AT Subthemes.
 * http://gruntjs.com/
 */

'use strict';

module.exports = function(grunt) {

	grunt.initConfig({

    pkg: grunt.file.readJSON('package.json'),

    sass_globbing: {
      page_layout_form: {
        files: {
          'layout/page-layout/sass/page-layout-form.scss': [
            'layout/page-layout/sass/**/*.scss',
            'layout/page-layout/sass/_page-layout-form-global.scss',
            '!layout/page-layout/sass/page-layout-global.scss'
          ]
        }
      }
    },

    sass: {
      uikit: {
        files: [{
          expand: true,
          cwd: 'styles/uikit/components/stylesheets',
          src: ['*.scss'],
          dest: 'styles/css/components',
          ext: '.css'
        }],
        options: {
          require: 'susy',
          precision: 5,
          outputStyle: 'expanded',
          imagePath: "../css/images",
          sourceMap: true
        }
      },
      page_layout: {
        files: [{
          expand: true,
          cwd: 'layout/page-layout/sass',
          src: ['**/*.scss'],
          dest: 'layout/page-layout/css',
          ext: '.css'
        }],
        options: {
          require: 'susy',
          precision: 5,
          outputStyle: 'expanded'
        }
      },
      plugin_layout: {
        files: [{
          expand: true,
          cwd: 'layout/plugin-layout/sass',
          src: ['*.scss'],
          dest: 'layout/plugin-layout/css',
          ext: '.css'
        }],
        options: {
          require: 'susy',
          precision: 5,
          outputStyle: 'expanded'
        }
      }
    },

    postcss: {
      uikit: {
        src: 'styles/css/components/**.css',
        options: {
          map: {
            inline: false,
            annotation: 'styles/css/components'
          },
          processors: [
            require('autoprefixer')({browsers: 'last 5 versions'})
          ]
        }
      },
      page_layout: {
        src: 'layout/page-layout/css/*/**.css',
        options: {
          processors: [
            require('autoprefixer')({browsers: 'last 5 versions'})
          ]
        }
      },
      plugin_layout: {
        src: 'layout/plugin-layout/css/**.css',
        options: {
          processors: [
            require('autoprefixer')({browsers: 'last 5 versions'})
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
        src: ['styles/css/components/**.css']
      }
    },

    watch: {
      uikit: {
        files: 'styles/uikit/components/**/*.scss',
        tasks: ['sass:uikit', 'postcss:uikit'],
        options: {
        //  livereload: 35729
        }
      },
      page_layout: {
        files: 'layout/page-layout/sass/**/*.scss',
        tasks: ['sass_globbing:page_layout_form', 'sass:page_layout']
      },
      plugin_layout: {
        files: 'layout/plugin-layout/sass/**/*.scss',
        tasks: ['sass:plugin_layout', 'postcss:plugin_layout']
      }

		}
	});

  grunt.loadNpmTasks('grunt-postcss');
  grunt.loadNpmTasks('grunt-sass');
  grunt.loadNpmTasks('grunt-sass-globbing');
  grunt.loadNpmTasks('grunt-contrib-csslint');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-browser-sync');

  grunt.registerTask('default', ['watch:uikit']);
};
