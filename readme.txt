=== Revisr ===
Contributors: ExpandedFronts
Tags: revisr, git, git management, revision tracking, revision, backup, database, database backup, database plugin, deploy, commit, bitbucket, github
Requires at least: 3.5.1
Tested up to: 3.9.2
Stable tag: trunk
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html

A simple plugin that integrates your git repository with WordPress.

== Description ==

Revisr allows you to manage your WordPress website with a git repository. With Revisr, you can:

* Track changes to the files and database of your WordPress installation
* Optionally use different database versions for each branch
* Commit and push changes to a remote repository (including Bitbucket and Github)
* Automatically pull new commits via POST hooks
* Pull changes down from a remote repository
* Easily toggle between branches
* Revert your website files and/or database to an earlier commit
* Discard any unwanted changes
* Manage .gitignore to prevent unwanted files/directories from being tracked

A must have plugin for deploying WordPress using git repositories.


== Installation ==

= Requirements = 
* A WordPress installation in the root folder of a Git repository
* Git must be installed on the server (most updated versions of cPanel have it preinstalled)
* The PHP exec() function enabled

= Instructions =
* Unzip the plugin folder and upload it to the plugins directory of your WordPress installation.
* Configure any remote repositories on the plugin settings page. Supports git through SSH or HTTPS.
* If authenticating with a remote repository via HTTPS with a password, be sure to password protect the .git directory via .htaccess or NGINX equivalent.

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

You should also make sure that the .sql backup files aren't publicly accessible. You can do this in Apache by adding the folling to your .htaccess file in the document root:

`
<FilesMatch "\.sql">
    Order allow,deny
    Deny from all
    Satisfy All
</FilesMatch>
`
If you're using NGINX, something similar to the below should work:
`
location ~ \.sql { deny all; }
`

It is also adviseable to add Revisr to the gitignore file via the settings page to make sure that reverts don't rollback the plugins' functionality. 

== Frequently Asked Questions ==

= How do the database backups/restores work? =
Every time you make a commit and check the box for "Backup database?", Revisr will take a mysqldump of the current database and commit it to the repository. Each backup overwrites the previous, since with Git we can revert to any previous version at any time. 

If you have the "Reset Database when Switching Branches" option checked, a few things will happen. When you click the button to toggle to a different branch or create a new branch, Revisr will backup the database and commit it to the repository. 

Then, Revisr switches branches and restores the last available database backup for that new branch. For example, you could create some posts on a branch called "dev", and switch back to the master branch. Once on master, you wouldn't see the posts on the dev branch because the database has essentially been kept seperate. Once you switch back to dev, you'll see your posts just how you left them. A more useful scenario would be testing out plugins or upgrades on a seperate branch without permanently affecting the database.

= Why are my commits timing out? =
This is likely an authentication issue. You can fix this by configuring your SSH keys or using the HTTPS authentication option on the settings page.

= Why aren't my commits being pushed to the remote repository? =
This is either an authentication issue or the remote branch is ahead of yours.

= Can I damage my site with this plugin? =
Care should be taken when dealing with upgrades that depend on the database. Taking verified backups before and after major changes is always recommended.

== Screenshots ==

1. The main dashboard of revisr.
2. Simple staging area that lets you decide what gets committed.
3. Easily view changes in files with Revisr's diffs. 
4. The commit history, filtered by branch.


== Changelog ==

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