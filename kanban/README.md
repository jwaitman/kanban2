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

## Security Features

The Kanban task management system includes several security features to protect your data and ensure secure access:

- **SSL Encryption**: All data transmitted between the client and server is encrypted using SSL/TLS. A self-signed certificate is generated during the setup, but you should replace it with a valid certificate from a trusted certificate authority.

- **Firewall Configuration**: The setup script configures UFW (Uncomplicated Firewall) to allow only necessary ports (80, 443, 22) and denies all other incoming connections.

- **Fail2Ban Integration**: This tool is installed and configured to protect the server from brute-force attacks.

- **Secure Apache Configuration**: The Apache configuration is hardened to disable unnecessary modules, restrict access to sensitive files, and prevent directory listing.

- **Environment File**: Sensitive configuration values are stored in a `.env` file in the project root, which is not accessible from the web.

## Troubleshooting Common Issues

- **Permission Denied Errors**: Ensure that the user running the commands has the necessary permissions. Use `sudo` where required.

- **Apache Not Starting**: Check the Apache error log at `/var/log/apache2/error.log` for details. Common issues include syntax errors in the configuration files or port conflicts.

- **Database Connection Issues**: Ensure that the MySQL service is running and that the credentials in the `.env` file are correct.

- **Node.js Errors**: Ensure that Node.js and npm are correctly installed and accessible in your PATH.

## Contributing

Contributions are welcome! Please read the `CONTRIBUTING.md` file for details on how to contribute to this project.

## License

This project is licensed under the MIT License - see the `LICENSE` file for details.

## Acknowledgments

- Inspired by the need for a secure, efficient task management system.
- Thanks to the open-source community for their invaluable tools and libraries.
