=== Revisr ===
Contributors: ExpandedFronts, mattshaw
Tags: revisr, git, git management, revision tracking, revision, backup, database, database backup, database plugin, deploy, commit, bitbucket, github, version control
Requires at least: 3.9.2
Tested up to: 4.3
Stable tag: trunk
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html

A simple plugin for managing your WordPress website with Git.

== Description ==

Revisr allows you to manage your WordPress website with a Git repository. With Revisr, you can:

* Track changes to your entire WordPress installation, or just the parts that you prefer
* Commit changes from within the WordPress dashboard
* Backup or restore your entire website in seconds
* Set up daily or weekly automatic backups
* Optionally push or pull changes to a remote repository, like Bitbucket or Github
* Test changes out before deploying them to another server
* Revert your website files and/or database to an earlier version
* Quickly discard any unwanted changes

A must have plugin for deploying and managing WordPress using Git repositories.


== Installation ==

= Requirements =
* A web server with Git and WordPress installed
* The PHP exec() function enabled

= Instructions =
* Unzip the plugin folder and upload it to the plugins directory of your WordPress installation.
* If the WordPress installation is not already in a Git repository, you will be able to create a new one in the Revisr Dashboard.
* Go to the Revisr settings page and adjust any settings as needed.

= Notes =
Please verify that database backups are working before attempting to revert the database, especially if attempting to do so on a production website. Backups use the mysqldump command, supported by most hosts/environments.

If you're using the HTTPS method to connect to the remote repository, the password for the remote repository will be stored in the '.git/config' file. You should take steps to prevent this from being publicly accessible. The following code added to a '.htaccess' file in the '.git/' directory will prevent public access:

`
content: Deny from all
`

If you're using NGINX, you'll have to update your configuration file with something similar to the following:
`
location ~ path/to/your-repo/.git {
        deny  all;
}
`

This issue can be avoided entirely by using SSH to authenticate, which is recommended in most cases. If using SSH, you will need to generate a SSH key on the server and add it to the remote repository (Bitbucket and Github both support SSH).

It is also adviseable to add Revisr to the .gitignore file via the settings page to make sure that reverts don't rollback any new functionality.

== Frequently Asked Questions ==

= How does Revisr handle the database? =
You have complete control, and can decide whether you want to track the entire database, just certain tables, or if you don't want to track the database at all. Then, during a backup, the tracked database tables are exported via "mysqldump". When importing or restoring the database to an earlier commit, Revisr first takes a backup of the existing database, creating a restore point from immediately before the import that can be reverted to if needed.

You can also set a "Development URL" that will be automatically replaced in the database during import- allowing for backups and restores that work on both your dev and live environments.

= Why aren't my commits being pushed to Bitbucket/GitHub? =
This is either an authentication issue or the remote branch is ahead of yours.

= Can I damage my site with this plugin? =
Care should be taken when dealing with upgrades that depend on the database. Taking verified backups before and after major changes is always recommended.

== Screenshots ==

1. The main dashboard of Revisr.
2. Simple staging area that lets you decide what gets committed.
3. Easily view changes in files with Revisr's diffs.
4. A comprehensive history of your website, with unlimited restore points.


== Changelog ==

= 1.9.5 =
* Added sizes to database tables for the "Let me decide..." option
* Added ability to search for commits by the 7-letter commit ID/hash
* Added link/filter for viewing tags on dashboard/commits page
* Improved plugin intiation
* Fixed bug with certain errors saved as an array when making a commit
* Fixed bug with first commit not showing correct branch
* Fixed bug with ".gitignore" not showing latest version of gitignore when updated outside of Revisr

= 1.9.4 =
* Added support for sockets in the DB_HOST constant
* Fixed bug causing error on PHP 5.2
* Fixed bug causing potential error with backups/imports on some systems

= 1.9.3 =
* Added option to use WordPress instead of MySQL for backups and imports
* Added ability to backup the database through the "New Commit" screen without any pending files
* Added ability to change the amount of events per page on the Revisr dashboard
* Improved .gitignore functionality, automatically remove cached files from repository index
* Several bugfixes and security improvements

= 1.9.2 =
* Improved error handling for commits, pushes, and pulls
* Fixed bug with saving Git username
* Fixed bug with PHP error reporting
* Fixed bug with push count when backing up DB and pushing at the same time
* Fixed CSS issue with viewing untracked tables after importing

= 1.9.1 =
* Fixed bug with "Import Pushes" checkbox not showing after saving
* Fixed bug with saving some settings in Windows
* Fixed CSS issue when viewing a branch with no commits
* Small cleanup

= 1.9 =
* Added support for multiple commit authors
* Added basic support for custom WordPress file structures
* Added support for PHP autoloading when available
* Added pagination to the "Recent Activity" table on the dashboard page
* Added "Debug" page to the "Revisr Settings" page
* Fixed bug with viewing diffs in Firefox,
* Fixed potential XSS and user escalation vulnerabilities, props @jdgrimes
* General UI improvements
* General performance improvements

= 1.8.3 =
* Fixed bug with spaces in filename

= 1.8.2 =
* Improved plugin performance
* Fixed bug with timezone on some sites
* Fixed bug with loading translation file
* Changed "Commit Changes" to read "Save Changes"
* Improved Remote URL validation

= 1.8.1 =
* Fixed bug resulting from changes in WordPress 4.1
* Fixed bug with storing webhook URL in some environments

= 1.8 =
* Added ability to track individual database tables
* Added ability to import tracked database tables while pulling changes
* Added ability to run a safe search/replace on the database during import to support multiple environments (supports serialization)
* Added unique token to the webhook to improve security (existing webhooks will need to be updated)
* Added fallback to the WordPress database class if mysqldump is not available
* Moved backups to 'wp-content/uploads/revisr-backups/' (path may vary) and automatically generate .htaccess
* Updated pending files count to only show for admins
* Updated error handling for commits
* Small UI improvements

= 1.7.2 =
* Tweaked permissions check to only check permissions if repository exists.

= 1.7.1 =
* Fixed potential PHP notice with tags widget
* Fixed permissions to just check the ".git/" directory

= 1.7 =
* Added ability to create a new repository if one does not already exist
* Added ability to create automatic daily or weekly backups
* Added ability to merge changes
* Added ability to add tags to commits
* Fixed bug with backing up the database when a port is used
* General improvements

= 1.6.3 =
* Improvements to the list of committed files
* Small bugfixes

= 1.6.2 =
* Added dedicated page for managing branches
* Database backups from the dashboard now automatically create new commits
* Fixed timezone bug on the dashboard
* Improvements to settings page
* Small UI improvements

= 1.6.1 =
* Small UI improvements
* Changed Recent Activity to show 15 items
* Fixed bug with multi-site installations
* Fixed bug with reverting files

= 1.6 =
* Added internationalization support
* Switched to human-friendly time diffs for Recent Activity
* Fixed bug causing dashboard to freeze in some environments
* Improved error handling
* Removed passthru() functions
* Code cleanup

= 1.5.2 =
* Fixed bug with adding certain files
* Restricted access to super admins for multisite installations

= 1.5.1 =
* Added support for remote DB hosts
* Fixed bug with deleting files

= 1.5 =
* Improved commit interface, added ability to stage individual files
* Added ability to see and revert to changes pulled from a remote
* Added ability to set the name of the remotes (defaults to origin)
* Added ability to set the path to MySQL
* Improved compatibility for Mac and Windows
* Bugfixes and general cleanup

= 1.4.1 =
* Added "Backup Database" button to the Quick Actions
* Added number of unpulled/unpushed commits to the Quick Action buttons
* Updated recent activity text

= 1.4 =
* Added ability to automatically pull changes from Bitbucket or Github (enabled on the settings page)
* Fixed bug causing call_user_func() error in some enviornments (including Windows)
* Additional validation on Git commands
* Additional error handling
* Fixed bug with saving .gitignore
* Fixed potential bug with viewing pending files
* Minor cleanup

= 1.3.2 =
* Bugfixes

= 1.3.1 =
* Added error handling
* Commits are no longer automatically pushed by default. This can be changed in the plugin settings page.
* Fixed issue with .gitignore showing as a pending file

= 1.3 =
* Added ability to track/revert changes to the database
* The commits listing now shows commits on the current branch by default
* Added basic compatibility check
* Added settings link to the plugin page

= 1.2.1 =
* Minor bugfixes

= 1.2 =
* Added ability to view the number of pending files in the admin bar
* Small cleanup, updated wording

= 1.1 =
* Bugfixes and improvements
* Added ability to view changes in files from a previous commit

= 1.0.2 =
* Minor bugfixes

= 1.0.1 =
* Updated readme.txt

= 1.0 =
* Initial commit
