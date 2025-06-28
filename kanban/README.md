# Kanban Task Management System

This is a security-hardened Kanban task management system.

## Installation on Ubuntu 24.04

This project includes a comprehensive setup script that automates the installation on a fresh Ubuntu 24.04 server.

1.  **Place the Project on Your Server**
    For best results and seamless integration with the automated Apache configuration, clone or move this project directory to `/var/www/`. The final path should be `/var/www/kanban`.

    ```bash
    # Example of cloning into the recommended directory
    sudo git clone https://github.com/your-repo/kanban.git /var/www/kanban
    cd /var/www/kanban
    ```

2.  **Run the Setup Script**
    Execute the setup script as a user with `sudo` privileges. This will install all required software (Apache, MySQL, PHP, Node.js), configure the virtual host, set up the database, and install all project dependencies.

    ```bash
    sudo chmod +x ./scripts/setup.sh
    ./scripts/setup.sh
    ```

3.  **Access the Application**
    The script configures everything needed to run the application. You can now access it in your web browser on the server at `http://kanban.local`.

    *Note: The script modifies the server's `/etc/hosts` file to make `kanban.local` resolve to `127.0.0.1`. If you want to access this from a different computer on your network, you will need to add `[your-server-ip] kanban.local` to your local machine's hosts file.*

4.  **Build Frontend Assets**
    For production use, you should build the optimized frontend assets.

    ```bash
    cd frontend
    npm run build
    ```

    For development, you can run the Vite development server:

    ```bash
    cd frontend
    npm run dev
    ```
