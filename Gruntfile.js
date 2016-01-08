module.exports = function(grunt) {

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        makepot: {
            target: {
                options: {
					domainPath: '/languages/',
					mainFile: 'revisr.php',
					potFilename: 'revisr.pot',
					potHeaders: {
						poedit: true,
						'report-msgid-bugs-to': 'http://wordpress.org/support/plugin/revisr',
						'last-translator': 'Revisr <support@expandedfronts.com>',
						'language-team': 'Revisr <support@expandedfronts.com>'
					},
					type: 'wp-plugin',
					updateTimestamp: true,
                }
            }
        }
    });

    grunt.loadNpmTasks('grunt-wp-i18n');

};
