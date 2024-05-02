function deploy () {
	cd ~/Sites/timber
	git checkout tags/$1 -b v$1
	rm ~/Sites/timber/timber.php
	rm -rf ~/Sites/timber/vendor
	rm -rf ~/Sites/timber/wp-content
	rm -rf ~/Sites/timber/timber-starter-theme
	git clone git@github.com:Upstatement/timber-starter-theme.git
	rm -rf ~/Sites/timber/timber-starter-theme/.git
	rm composer.lock

	# Download dependencies for the maximum compatible PHP version.
	composer config platform.php 7.2.5
	composer install --no-dev --optimize-autoloader

	# Install the lowest compatible version of Twig.
	composer update twig/twig:1.44.7 --no-dev

	rm -rf ~/Sites/timber/vendor/upstatement/routes/.git
	cd ~/Sites/timber-wp
	mkdir tags/$1

	# Clean the Starter Theme of stuff we don't want on WP.org
	rm ~/Sites/timber/timber-starter-theme/composer.json
	rm ~/Sites/timber/timber-starter-theme/composer.lock
	rm -rf ~/Sites/timber/timber-starter-theme/vendor
	rm -rf ~/Sites/timber/timber-starter-theme/bin

	cp -r ~/Sites/timber/lib tags/$1/lib
	cp -r ~/Sites/timber/timber-starter-theme tags/$1/timber-starter-theme
	cp -r ~/Sites/timber/vendor tags/$1/vendor
	cp ~/Sites/timber/LICENSE.txt tags/$1/LICENSE.txt
	cp ~/Sites/timber/README.md tags/$1/README.md
	cp ~/Sites/timber/readme.txt tags/$1/readme.txt
	cp ~/Sites/timber/bin/timber.php tags/$1/timber.php
	svn add tags/$1
	cd tags/$1
	svn commit -m "updating to $1"
	cd ~/Sites/timber-wp/trunk
	rm -rf ~/Sites/timber-wp/trunk/lib
	rm -rf ~/Sites/timber-wp/trunk/timber-starter-theme
	rm -rf ~/Sites/timber-wp/trunk/vendor
	cp -r ~/Sites/timber/lib ~/Sites/timber-wp/trunk
	cp -r ~/Sites/timber/timber-starter-theme ~/Sites/timber-wp/trunk
	cp -r ~/Sites/timber/vendor ~/Sites/timber-wp/trunk
	cp ~/Sites/timber/LICENSE.txt ~/Sites/timber-wp/trunk/LICENSE.txt
	cp ~/Sites/timber/README.md ~/Sites/timber-wp/trunk/README.md
	cp ~/Sites/timber/readme.txt ~/Sites/timber-wp/trunk/readme.txt
	cp ~/Sites/timber/bin/timber.php ~/Sites/timber-wp/trunk/timber.php
	svn commit -m "updating to $1" readme.txt
	svn commit -m "updating to $1" timber.php
	cd ~/Sites/timber
	git checkout composer.json
}

#!/usr/bin/env bash
read -p "Did you update the changelog and version numbers?" -n 1 -r
echo    # (optional) move to a new line
if [[ $REPLY =~ ^[Yy]$ ]]
then
	php -v
    echo "Setting up version " $1
	echo "You still need to use Versions to send to WP.org"

	deploy $1
fi



