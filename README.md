momoko-cms
==========

MomoKO Content Managment System - The web is *yours* again!

Installation Instructions
-------------------------

### 1. Acquire MomoKO

There are two quick and easy methods for getting your hands on MomoKO's code. The first and best method is using git on your server, but this requires SSH access. If you don't have SSH access, or don't know what SSH is you can download a ZIP file from our repo. I will describe both methods below.

#### 1.1 Using git

The steps outlined here will help you set up a folder on your server as a local clone of our git repository. This method is the quickest and allows you to easily upgrade when new updates are made available. SSH access to your web host is **required**!

1. Log in to your server via SSH, instructions on this procedure vary depending on your SSH client. Consult your client's instruction manual (ex. `man ssh`) for details.
2. Once you have an shell prompt create a folder for MomoKO or change to your web folder either `mkdir momoko && cd momoko` or `cd public_html` replace momoko with the new folder name or public_html with the correct web document root. Keep in mind that some linux HTTP servers may use folders such as /var/www/html. Take a look at the documentation of your server's operating system or the manual for your HTTP server (ex. `man apache2`) for more information on this configuration.
3. Now clone into our github repo; `git clone https://github.com/jjon-saxton/momoko-cms.git ./`. This will initialize the folder and download MomoKO into the current folder you just created and switched to. The default branch is latest_stable, if you want a different one, run `git checkout 2.0-RC`, replace 2.0-RC with the branch you want. You can run `git branch -a` for a list of branches available.
6. Proceed to 'Configuring MomoKO'

NOTE: you will see two "latest" branches as well as several branches with a version number (2.0-RC, 2.0-STABLE, 2.1-UNSTABLE, 2.1-BETA). As a general rule, numbered branches are what we call "working branches". That is to say code is submitted here to be evaluated. Which also means they can fluctuate and become somewhat unstable (dispate some names like 2.0-STABLE). Furthermore these branches are not officially supported and should only be used if you are notified to use a specific branch or if you would like to submit code for review (if you are a developer). Finally the "latest" branches are what we call "snapshot branches". They only contain reviewed and approved code and should be the most stable of their kind. Keep in mind that the "latest_beta" branch is still a beta branch and will include bugs that have yet to be discovered and even some known bugs, though none that should be damaging.

#### 1.2 Using FTP

If you have SSH access and prefer not to set up or install git, or you want to upload from your local machine you may use wget or another FTP client to acquire MomoKO's source code in a zip archive. Please note: though this is written specifically for wget on Linux, or FireFTP you may use wget on any supported operating system, or any FTP client you choose. The important thing to note here is that you will be logging into the FTP server anonymously!

##### 1.2.1 Text-based: wget

1. Open a terminal and/or log in. If you wish to download and extract directly from your server login to it via SSH.
2. To download the latest stable version of MomoKO you need only type `wget --user=anonymous@momokocms.org ftp://momokocms.org/core/latest_stable/*.zip`. The '*' is a wildcard which will make wget grab all zip files in the directory '/core/latest_stable' There is only one archive in this folder at all times and it is the latest stable version of MomoKO so this will only grab the files you need. if you want a different version type `wget --user=anonymous@momokocms.org ftp://ftp.momokocms.org/core/momoko-cms-myversion.zip` replacing 'myversion' with the version you want. Keep in mind we use an x.x.xa version system. If you don't know what versions are available it's best to get a listing of the '/core/' directory using Linux's built in FTP client or a graphical one.
3. Extract the zip archive. This is a zip file so unzip will be needed to extract it. Type `unzip momoko-cms-1.1.2.zip` or the file name you acquired from wget, you can also use `unzip momoko-cms*.zip` if you aren't sure. This will create a folder like 'momoko-cms-latest_stable' or 'momoko-cms-2.0-STABLE'. You may wish to simply rename this to 'momoko' or something similar.
4. Proceed to 'Configure MomoKO'

##### 1.2.2 Graphically: FireFTP

1. Open your favorite FTP client, for this example we'll be using FireFTP, so we will be opening Firefox then clicking FireFTP in the toolbar or developer menu, but you may open just about any client.
2. Click the drop down and select 'create an account' to open the account manager. In some clients you need simply choose 'connect'.
3. Find the 'host' field and type 'momokocms.org'.
4. Enter anonymous@momokocms.org as the username, but leave the password field blank. Click 'Ok' then click 'connect'.
5. Welcome! On the right pane (or remote pane) you will see a list of directories click 'core' to open it. Select the zip of the version you want. Note: if you just want the latest stable version enter the 'latest_stable' directory, this houses only one archive, so just select that one.
6. Double click the archive you selected, or right click and click download. You may also drag and drop it anywhere in the left (local) pane.
7. After the file has downloaded right click it and select open to open it in your computer's default archive manager.
8. We won't go into details here as archive managers vary widely, but you need to extract all of the files into a folder, while keeping the directory structure in the archive.
9. Use FTP or whatever protocol you choose to upload MomoKO to your server.
10. Proceed to 'Configure MomoKO'.

### 2. Configure MomoKO

Once she is on your server you need to set her up to connect to a database, create required tables on, change your site settings, and create an administrator for your site. Once this is done, you will be able to login, create pages, and add additional users. Fortunately this task is made simple by the inclusion of a finalization script. Two versions of this script are available for flexibility. If you completed the previous step via SSH, you can run the finalization right there using the CLI version. If you did not use SSH, or simply wish to finish the installation in a web-browser you can use the web version.

#### 2.1 CLI: Run install.php over SSH

To use this method you will need SSH access to your server!

1. Login to you server via SSH if you are not already logged in.
2. Navigate to your MomoKO installation and change to `cli`
3. Run `install.php`
    1. Ensure `install.php` is executable and type: `./install.php`
    2. If this does not work also try: `php ./install.php`
4. Follow the onscreen instructions.

#### 3.2 Web: Run mk_install.php via your browser

1. Open your browser and navigate to the domain and location (URL) you set for MomoKO
2. You should be redirected to `mk_install.php`
3. Follow the onscreen instructions.

### Congratulations!

Your site is now configured and ready. Login as the administrator you created above and begin adding content!

Upgrading MomoKO
----------------

Upgrading MomoKO can be simple if you used git to acquire her. Otherwise the procedure is a bit more difficult.

### 1 - Update your software to 2.0

If you used git to acquire MomoKO you've already set up a local repository just log into your server via SSH and change over to the directory you install MomoKO in. Also, assuming you followed the instructions in the README you are already on the latest_stable branch. Just run `git pull` and git will update the software.

If you did no use the latest_stable branch (for example, if you skipped the 2.0 release and switched to 1.6-STABLE). You will have to switch branches. Simply run `git checkout latest_stable`. Do NOT switch to 2.1-RC or 2.1-STABLE unless you are a developer (see the note in the Installation instructions).

If you did not acquire MomoKO with git you will be able to acquire new MomoKO zip archives from FTP using the FTP procedures outlined above. The archive in /core/latest_stable/ will have been updated so you can simply re-download that if you wish or pick a version higher than the one you have in /core/

### 2 - Set up a 2.1 compatible database

The easiest way to ensure your database is compatible with 2.1 is to set up a new one. You can use the same connection settings, but the best way to set this up is to delete your current "database.ini" and follow the instructions under "Configure MomoKO" in the README. Ensure you use a different table prefix. This will allow you to keep your old tables for reference later.

### 3 - Import settings and users

You can create an export of your old settings table using any MySQL client. The instructions to do so vary by client. Once the export is available, your can then import it into the new settings table. This will keep your settings the same on both installs.

The new user table should also be mostly compatible with the old one. You should be able to follow a similar procedure and import all your current users. If it fails, you may have to add them each in manually via the cli or dashboard tools.

### 4 - Import your content

You'll want to create a `*.zip` file with your pages, files, and folder in it. The easiest way to do this on a *nix system is to use zip. Go to the folder that contains your pages and files folder. On 1.1 this may be under the `assets` folder in your document root, on 1.5 and 1.6 these may be right under the root folder. You can always check your sites settings to find the exact folder. Once there run `zip -r my-export.zip pages files` to add the required folders to a zip file. This may get more complicated if your folders are in two different parent folders. In which case it is probably easiest just to make copies of the folders in the same parent folder and run the above command from there.

Go to your new MomoKO 2.1 site and log in as an administrator. Go to the new addin section in the switchboard sidebar and select 'importer'. Select MomoKO 1.0-1.6 as the type of data then browse for the zip file you just created. The importer will upload this file and prepare its contents for import into the new database. Select what data you would like to import then click import and the new data will be added.

### 5 - For the future

At this time this project is being closed and has no future. I may revisit it at a future date, but there are no plans for that at this time.
