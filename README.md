#Revisr [![Build Status](https://travis-ci.org/ExpandedFronts/revisr.svg?branch=master)](https://travis-ci.org/ExpandedFronts/revisr) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ExpandedFronts/revisr/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ExpandedFronts/revisr/?branch=master)

##Description##

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

## Installation ##

### Requirements ###

* A web server with Git and WordPress installed
* The PHP exec() function enabled

### Instructions ###

* Unzip the plugin folder and upload it to the plugins directory of your WordPress installation.
* If the WordPress installation is not already in a Git repository, you will be prompted to initialize a new one.
* Configure the plugin settings.

### Notes ###
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

It is also adviseable to add Revisr to the gitignore file via the settings page to make sure that reverts don't rollback the plugins' functionality. 

## Changelog ##

#### 1.8 ####
* Added ability to track individual database tables
* Added ability to import tracked database tables while pulling changes
* Added ability to run a safe search/replace on the database during import to support multiple environments (supports serialization)
* Added unique token to the webhook to improve security (existing webhooks will need to be updated)
* Added fallback to the WordPress database class if mysqldump is not available
* Moved backups to 'wp-content/uploads/revisr-backups/' (path may vary) and automatically generate .htaccess
* Updated pending files count to only show for admins
* Updated error handling for commits
* Small UI improvements

#### 1.7.2 ####
* Tweaked permissions check to only check permissions if repository exists.

#### 1.7.1 ####
* Fixed potential PHP warning with tags widget
* Fixed permissions to just check the ".git/" directory

#### 1.7 ####
* Added ability to create a new repository if one does not already exist
* Added ability to create automatic daily or weekly backups
* Added ability to merge changes
* Added ability to add tags to commits
* Fixed bug with backing up the database when a port is used
* General improvements

#### 1.6.3 ####
* Improvements to the list of committed files
* Small bugfixes

#### 1.6.2 ####
* Added dedicated page for managing branches
* Database backups from the dashboard now automatically create new commits
* Fixed timezone bug on the dashboard
* Improvements to settings page
* Small UI improvements

#### 1.6.1 ####
* Small UI improvements
* Changed Recent Activity to show 15 items
* Fixed bug with multi-site installations
* Fixed bug with reverting files

#### 1.6 ####
* Added internationalization support
* Switched to human-friendly time diffs for Recent Activity
* Fixed bug causing dashboard to freeze in some environments
* Improved error handling
* Removed passthru() functions
* Code cleanup

#### 1.5.2 ####
* Fixed bug with adding certain files
* Restricted access to super admins for multisite installations

#### 1.5.1 ####
* Added support for remote DB hosts
* Fixed bug with deleting files

#### 1.5 ####
* Improved commit interface, added ability to stage individual files
* Added ability to see and revert to changes pulled from a remote
* Added ability to set the name of the remotes (defaults to origin)
* Added ability to set the path to MySQL
* Improved compatibility for Mac and Windows
* Bugfixes and general cleanup

#### 1.4.1 ####
* Added "Backup Database" button to the Quick Actions
* Added number of unpulled/unpushed commits to the Quick Action buttons
* Updated recent activity text

#### 1.4 ####
* Added ability to automatically pull changes from Bitbucket or Github (enabled on the settings page)
* Fixed bug causing call_user_func() error in some enviornments (including Windows)
* Additional validation on Git commands
* Additional error handling
* Fixed bug with saving .gitignore
* Fixed potential bug with viewing pending files
* Minor cleanup

#### 1.3.2 ####
* Bugfixes

#### 1.3.1 ####
* Added error handling
* Commits are no longer automatically pushed by default. This can be changed in the plugin settings page.
* Fixed issue with .gitignore showing as a pending file

#### 1.3 ####
* Added ability to track/revert changes to the database
* The commits listing now shows commits on the current branch by default
* Added basic compatibility check
* Added settings link to the plugin page

#### 1.2.1 ####
* Minor bugfixes

#### 1.2 ####
* Added ability to view the number of pending files in the admin bar
* Small cleanup, updated wording

#### 1.1 ####
* Bugfixes and improvements
* Added ability to view changes in files from a previous commit

#### 1.0.2 ####
* Minor bugfixes

#### 1.0.1 ####
* Updated readme.txt

#### 1.0 ####
* Initial commit