#!/bin/bash

# This script is designed to work on a fresh Ubuntu 24.04 installation.
# It will install all necessary system packages and then set up the application.

# Exit on any error
set -e

# --- System-Level Dependencies ---

echo "--- Updating system packages ---"
sudo apt-get update
sudo apt-get upgrade -y

echo "--- Installing Apache, MySQL, and PHP ---"
sudo apt-get install -y apache2 mysql-server php libapache2-mod-php php-mysql php-mbstring php-xml php-json php-bcmath php-cli unzip curl

# --- Secure MySQL and Set Up Database User ---
echo "--- Configuring MySQL and creating a database user ---"

# Generate a random password
DB_PASS=$(openssl rand -base64 12)
DB_USER="kanban_user"
DB_NAME="kanban_db"

# Note: This runs MySQL commands without password prompting. This is suitable for a fresh, automated install.
# On an existing system, you might need to enter the root password.
sudo mysql -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;"
sudo mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
sudo mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

echo "Database and user created successfully."

echo "--- Installing Composer (PHP Dependency Manager) ---"
if ! command -v composer &> /dev/null
then
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    # Installer hash may need to be updated periodically from getcomposer.org/download
    php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); exit(1); } echo PHP_EOL;"
    php composer-setup.php
    php -r "unlink('composer-setup.php');"
    sudo mv composer.phar /usr/local/bin/composer
else
    echo "Composer is already installed."
fi

echo "--- Installing Node.js and npm ---"
if ! command -v node &> /dev/null
then
    # Using NodeSource to get a modern version of Node.js
    curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
    sudo apt-get install -y nodejs
else
    echo "Node.js is already installed."
fi

echo "--- Configuring Apache ---"

PROJECT_PATH=$(pwd)
APACHE_CONF_FILE="/etc/apache2/sites-available/kanban.conf"

sudo tee $APACHE_CONF_FILE > /dev/null <<EOL
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    ServerName kanban.local
    DocumentRoot $PROJECT_PATH/public

    <Directory $PROJECT_PATH/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOL

echo "--- Enabling new site and restarting Apache ---"
sudo a2ensite kanban.conf
sudo a2dissite 000-default.conf
sudo a2enmod rewrite
sudo systemctl restart apache2

echo "--- Adding kanban.local to /etc/hosts ---"
echo "127.0.0.1 kanban.local" | sudo tee -a /etc/hosts

echo "--- Application-Level Setup ---"

# --- Project-Specific Dependencies & Setup ---

# 1. Install PHP dependencies
if [ -f "composer.json" ]; then
    composer install --no-interaction --prefer-dist
fi

# 2. Install frontend dependencies
if [ -f "frontend/package.json" ]; then
    (cd frontend && npm install)
fi

# 3. Create .env file
echo "--- Creating .env file with generated credentials ---"
cp .env.example .env
sed -i "s/DB_HOST=.*/DB_HOST=127.0.0.1/" .env
sed -i "s/DB_NAME=.*/DB_NAME=$DB_NAME/" .env
sed -i "s/DB_USER=.*/DB_USER=$DB_USER/" .env
sed -i "s/DB_PASS=.*/DB_PASS=$DB_PASS/" .env

# 4. Set up database schema and seed data
echo "--- Setting up database schema and initial data ---"

if [ -f "db/schema.sql" ]; then
    # Use the credentials from the .env file to run the schema import
    mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME < db/schema.sql
fi

if [ -f "db/seed.sql" ]; then
    # Use the credentials from the .env file to run the seed import
    mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME < db/seed.sql
fi

echo "--- Setup complete! ---"
echo "You can now access your application at http://kanban.local"
echo "Next steps:"
echo "1. Navigate to the 'frontend' directory."
echo "2. Run 'npm run build' to compile assets for production."
echo "3. Or run 'npm run dev' to start the frontend development server."
