=== Revisr ===

Contributors: ExpandedFronts
Tags: git, revisr, revision, version control, commit, wordpress
Requires at least: 3.9.1
Tested up to: 3.9.1
Stable tag: trunk
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html

A simple plugin that integrates your git repository with WordPress.

== Description ==
Revisr allows you to synchronize your WordPress website with a git repository. With Revisr, you can:

* Track changes to the files of your WordPress installation
* Commit and push changes to a remote repository (including Bitbucket and Github)
* Pull changes down from a remote repository
* Easily toggle between unlimited branch
* Revert to an earlier commit
* Discard any unwanted changes
* Manage .gitignore to prevent unwanted files/directories from being tracked

A must have plugin for WordPress development.

== Installation ==

Revisr requires that git be installed on the server, and the repository is in the root directory of the WordPress installation. Revisr also requires php exec to be enabled on the server (this can be configured in your php.ini).

Unzip the plugin and upload the /revisr/ folder to your plugins directory. Once activated, Revisr will automatically use the repository in the WordPress directory. If remote repositories are configured in the local repository, Revisr will use those for pushes and pulls. A remote repository can also be configured on the plugin settings page.

== Frequently Asked Questions ==

= Why are my commits timing out? =
This is likely an authentication issue. You can fix this by configuring your SSH keys or using the HTTPS authentication option on the settings page.

= Can I damage my site with this plugin? =
Absolutely. Care should be taken when dealing with upgrades that depend on the database. For example, upgrading to the next major version of WordPress and later reverting could cause issues if there are significant changes to the database.

== Screenshots ==
1. The main dashboard of revisr.
2. The commit history.
3. Git settings and options.
4. Easily view changes in files.

== Changelog ==

v1.0 - Initial release.