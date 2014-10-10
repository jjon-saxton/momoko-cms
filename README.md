momoko-cms
==========

MomoKO Content Managment System - The web is *yours* again!

Installation Instructions
-------------------------

### 1. Acquire MomoKO

There are two quick and easy methods for getting your hands on MomoKO's code. Both methods make use of her git repo. The first and best method is using git on your server, but this requires SSH access. If you don't have SSH access, or don't know what SSH is you can download a ZIP file from our repo. I will describe both methods below.

#### 1.1 Using git

The steps outlined here will help you set up a folder on your server as a local git repository. You will than be able to set our repo up as a remote origin and pull in MomoKO. This method is the quickest and allows you to easily upgrade when new updates are made available. SSH access to your web host is **required**!

1. Log in to your server via SSH, instructions on this procedure vary depending on your SSH client. Consult your client's instruction manual for details.
2. Once you have an shell prompt create a folder for MomoKO or change to your web folder either `mkdir momoko && cd momoko` or `cd public_html` replace momoko or public_html with the correct folder name.
3. Set-up git in the folder you switched to; `git init`
4. Set our repo as this folder's remote origin: `git remote add origin https://github.com/jjon-saxton/momoko-cms.git`
5. Download from git: `git pull origin latest_stable`
6. Proceed to 'Configuring MomoKO'

#### 1.2 Using FTP

If you have SSH access and prefer not to set up or install git, or you want to upload from your local machine you may use wget or another FTP client to acquire MomoKO's source code in a zip archive. Please note: though this is written specifically for wget on Linux, or FireFTP you may use wget on any supported operating system, or any FTP client you choose. The important thing to note here is that you will be logging into the FTP server anonymously!

##### 1.2.1 Text-based: wget

1. Open a terminal and/or log in. If you wish to download and extract directly from you server login to it via SSH.
2. To download the latest stable version of MomoKO you need only type `wget ftp://saxton-solutions/pub/momoko/core/latest_stable/*.zip`. The '*' is a wildcard which will make wget grab all zip files in the directory 'pub/momoko/core/latest_stable' There is only one archive in this folder at all times and it is the latest stable version of MomoKO so this will only grab the files you need. if you want a different version type `wget ftp://saxton-solutions.com/pub/momoko/core/momoko-cms-myversion.zip` replacing 'myversion' with the version you want. Keep in mind we use an x.x.xa version system. If you don't know what versions are available it's best to get a listing of the 'pub/momoko/core/' directory using Linux's built in FTP client or a graphical one.
3. Extract the zip archive. This is a zip file so unzip will be needed to extract it. Type `unzip momoko-cms-1.1.2.zip` or the file name you acquired from wget, you can also use `unzip momoko-cms*.zip` if you aren't sure. This will create a folder like 'momoko-cms-latest_stable' or 'momoko-cms-1.1-STABLE'. You may wish to simply rename this to 'momoko' or something similar.
4. If you ran all of these under an SSH login to your remote server than you are done and may proceed to 'Configure MomoKO'. If you did this locally you may wish to still proceed to 'Configure MomoKO' if you do not have SSH access, just don't forget to upload the **entire** folder to your server via FTP, FTPS, or whatever files upload method is available *before* proceeding to 'Preparing your database'. Make sure the file upload method can support folder uploads.

##### 1.2.2 Graphically: FireFTP

1. Open your favorite FTP client, for this example we'll be using FireFTP, so we will be opening Firefox then clicking FireFTP in te toolbar or developer menu, but you may open just about any client.
2. Click the drop down and select 'create an account' to open the account manager. In some clients you need simply choose 'connect'.
3. Find the 'host' field and type 'saxton-solutions.com'.
4. Leave any 'login' and 'password' fields blank and find the checkbox labeled 'Anonymous' or something similar and make sure it is checked. Click 'Ok' then click 'connect'.
5. Weclome to SaxtonSolutions! On the right pane (or remote pane) you will see a list of directories, expand them like so pub->momoko and click 'core' to open it. Select the zip of the version you want. Note: if you just want the latest stable version enter the 'latest-stable' directory, this houses only one archive, so just select that one.
6. Double click the archive you selected, or right click and click download. You may also drag and drop it anyone in the left (local) pane.
7. After the file has downloaded right click it and select open to open it in your computer's default archive manager.
8. We won't go into details here as archive managers vary widely, but you need to extract all of the files into a folder, while keeping the directory structure in the archive.
9. Proceed to 'Configure MomoKO', but upload the **entire** folder to your server *before* moving on to 'Preparing your database'

### 2. Configuring MomoKO

Once you have MomoKO on your server and accessable to the world, you will have to configure her. In the folder you place MomoKO in on your server there is a sub-folder called `assets` in there is a sub-folder called `etc`. `etc` is where the configuration files are kept. Currently only one file, `version.nfo.txt` exists. This file simply holds the current version number. To configure MomoKO copy the contents of the sub-folder `examples` into the folder `etc`. Over SSH the following command should work: `cp -r ./examples/* ./`

You should get to files, `dal.conf.txt` and `main.conf.txt`. First open `main.conf.txt`, this is used to set up MomoKO's environment variables so she knows where things are and what things are called. We will define the settings below.

#### 2.1 main.conf.txt Settings

basedir
:	This sets the location of your MomoKO's scripts. This should be set to the *absolute* path to the folder MomoKO was put in when you acquired her.

pagedir
:	This sets the location where MomoKO's pages will be stored. In the example, we show the absolute path to assets/pages, but this can be *any* folder on your file system that your web server software can read and write to.

filedir
:	Like the above, but sets were MomoKO will look for photos, videos, and other types of files other than pages.

tempdir
:	Like the above, but sets where MomoKO will store temporary files

domain
:	Not set in the example, this is where you can store the fully qualified domain name of your site. If you leave this blank MomoKO will try to use global environment variables to guess your domain.

location
:	The location of you site according to your web server software. This is often just the folder underneath you web document folder where you put MomoKO, but this can be different if your server uses userdirs. Please consult your server documentation if this is the case. If in doubt, leave blank and see if there are errors.

session
:	Gives the MomoKO session a name, by default this is 'mk', there should be no need to change it unless you have multiple MomoKO instances on your server or something else that sets a cookie with that name.

salt
:	Used to encrypt passwords, the salt characters are prepended to the password and alters the output. The example sets this to 'mk', there should be no need to change this unless you simply want more security by being more unique.

rewrite
:	If your server supports mod_rewrite you can use included rewrite rules. Move the `.htaccess` file to your MomoKO's basedir and set this to true. This will generate more search-engine and human friendly URLS

#### 2.2 dal.conf.txt Settings

type
:	This is the type of database server you have. This instructs the Database Abstraction Layer on what driver to load. Please ensure a driver exists and/or as been installed for your server type. Also uses all lower-case for this value. If you use a MySQL server, type mysql here; for SQLite2 type sqlite, for SQLite3 use sqlite3.

table_pre
:	A prefix to prepend to table names. This can be anything you chose, but we advise you to have an underscore '_' character at the end. Leaving this blank will generate simple names with no prefix. You might do this for a dedicated database.
file
:	For SQLite2 and SQLite3, specify the *absolute* path to the database file.

host
:	For MySQL and others, specify the fully qualified domain name for your database server.

user
:	The user name of MomoKO's database user. This user must have full read write rights to the database listed below. The easiest method is to create this user and than chose to create a database with their name and grant them all privledges.

password
:	The password of the user above. Do **NOT** leave this blank. For security this user **needs** a password! The only exception would be an SQLite2 or SQLite3 database where no user is used.

default
:	The name of the default database. The user specified above needs full privledges for this database!

### 3. Prepare your database

Once configured you need to set up required tables on your database and create an administrator for your site. Once this is done, you will be able to login, create pages, and additional users. Fortunately this task is made simple by the inclusion of a finalization script. This procedure can only be completed via SSH at this time. A web front-end will be created shortly, which will allow you to complete this procedure in your web browser.

#### 3.1 Run install.php CLI over SSH

To use this method you will need SSH access to your server!

1. Login to you server via SSH
2. Navigate to your MomoKO installation and change to `assets/php/cli`
3. Run `install.php`
    1. Ensure `install.php` is executable and type: `./install.php`
    2. If this files try: `php ./install.php`
4. Follow the onscreen instructions.

### Congratulations!

Your site is now configured and ready. Login as the administrator you created above and begin adding content!

Upgrading MomoKO
----------------

Upgrading MomoKO can be simple if you used git to acquire her. Otherwise the procedure is a bit more difficult.
