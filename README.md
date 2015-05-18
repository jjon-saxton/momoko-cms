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
2. To download the latest stable version of MomoKO you need only type `wget --user=anonymous@momokocms.org ftp://momokocms.org/core/latest_stable/*.zip`. The '*' is a wildcard which will make wget grab all zip files in the directory 'public_ftp/core/latest_stable' There is only one archive in this folder at all times and it is the latest stable version of MomoKO so this will only grab the files you need. if you want a different version type `wget --user=anonymous@momokocms.org ftp://ftp.momokocms.org/core/momoko-cms-myversion.zip` replacing 'myversion' with the version you want. Keep in mind we use an x.x.xa version system. If you don't know what versions are available it's best to get a listing of the 'public_ftp/core/' directory using Linux's built in FTP client or a graphical one.
3. Extract the zip archive. This is a zip file so unzip will be needed to extract it. Type `unzip momoko-cms-1.1.2.zip` or the file name you acquired from wget, you can also use `unzip momoko-cms*.zip` if you aren't sure. This will create a folder like 'momoko-cms-latest_stable' or 'momoko-cms-1.1-STABLE'. You may wish to simply rename this to 'momoko' or something similar.
4. Proceed to 'Configure MomoKO'

##### 1.2.2 Graphically: FireFTP

1. Open your favorite FTP client, for this example we'll be using FireFTP, so we will be opening Firefox then clicking FireFTP in the toolbar or developer menu, but you may open just about any client.
2. Click the drop down and select 'create an account' to open the account manager. In some clients you need simply choose 'connect'.
3. Find the 'host' field and type 'momokocms.org'.
4. Leave enter anonymous@momokocms.org as the username, but leave the password field blank. Click 'Ok' then click 'connect'.
5. Weclome! On the right pane (or remote pane) you will see a list of directories click 'core' to open it. Select the zip of the version you want. Note: if you just want the latest stable version enter the 'latest_stable' directory, this houses only one archive, so just select that one.
6. Double click the archive you selected, or right click and click download. You may also drag and drop it anywhere in the left (local) pane.
7. After the file has downloaded right click it and select open to open it in your computer's default archive manager.
8. We won't go into details here as archive managers vary widely, but you need to extract all of the files into a folder, while keeping the directory structure in the archive.
9. Use FTP or whatever protocol you choose to upload MomoKO to your server.
10. Proceed to 'Configure MomoKO'.

#### 1.3 Using momokocms.org

If you don't have SSH access to your server and don't want to acquire and FTP client, and/or set it up to connect to our server, you can download archives from our website, momokocms.org. This will house both zip and tar.gz archives for the latest stable version. You will have to use another method to download other versions.

1. Open your favorite web brownser and navigate to http://www.momokocms.org.
2. If you are using Windows click the "Download .zip file" button, if you are using Linux or Mac OSX you may use either button.
3. Extract the zip file, be sure to keep the directory structure, but you can change the name of the root directory if you wish.
4. Upload to your server
5. Proceed to 'Configure MomoKO'

### 2. Configure MomoKO

Once she is on your server you need to set her up to connect to a database, create required tables on, change your site settings, and create an administrator for your site. Once this is done, you will be able to login, create pages, and additional users. Fortunately this task is made simple by the inclusion of a finalization script. Two versions of this script are available for flexibility. If you completed the previous step via SSH, you can run the finalization right there using the CLI version. If you did not use SSH, or simply wish to finish the installation in a web-browser you can use the web version.

#### 2.1 CLI: Run install.php over SSH

To use this method you will need SSH access to your server!

1. Login to you server via SSH if you are not already logged in.
2. Navigate to your MomoKO installation and change to `cli`
3. Run `install.php`
    1. Ensure `install.php` is executable and type: `./install.php`
    2. If this does not work also try: `php ./install.php`
4. Follow the onscreen instructions.

#### 3.2 Web: Run mk_install.php via your browser

1. Open your browser and navigate to the domain and location (URL) you placed MomoKO
2. You should be redirected to `mk_install.php`
3. Follow the onscreen instructions.

### Congratulations!

Your site is now configured and ready. Login as the administrator you created above and begin adding content!

Upgrading MomoKO
----------------

Upgrading MomoKO can be simple if you used git to acquire her. Otherwise the procedure is a bit more difficult.
