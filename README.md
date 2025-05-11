# What is NGS

NGS (Naghashyan Framework) is a high performance, component-based PHP framework for rapidly developing modern Web applications.

## What is NGS Best for?

NGS is a generic Web programming framework, meaning that it can be used for developing all kinds of Web applications using PHP. Because of its component-based architecture and sophisticated caching support, it is especially suitable for developing large-scale applications such as portals, forums, content management systems (CMS), e-commerce projects, RESTful Web services, and so on.

## How does NGS Compare with Other Frameworks?

If you're already familiar with another framework, you may appreciate knowing how NGS compares:

* Like most PHP frameworks, NGS implements the MVC (Model-View-Controller) architectural pattern and promotes code organization based on that pattern.
* NGS takes the philosophy that code should be written in a simple yet elegant way. NGS will never try to over-design things mainly for the purpose of strictly following some design pattern.
* NGS is a full-stack framework providing many proven and ready-to-use features: query builders and ActiveRecord for both relational and NoSQL databases; RESTful API development support; multi-tier caching support; and more.
* NGS is extremely extensible. You can customize or replace nearly every piece of the core's code. You can also take advantage of NGS's solid extension architecture to use or develop redistributable extensions.
* High performance is always a primary goal of NGS.

## Requirements and Prerequisites

NGS requires PHP 7.4.0 or above. You can find more detailed requirements for individual features by running the requirement checker included in every NGS release.

Using NGS requires basic knowledge of object-oriented programming (OOP), as NGS is a pure OOP-based framework. NGS also makes use of the latest features of PHP, such as [namespaces](http://www.php.net/manual/en/language.namespaces.php) and [traits](http://www.php.net/manual/en/language.oop5.traits.php). Understanding these concepts will help you more easily pick up NGS.

## 🚀 Starting a New NGS Project with Docker

To start a new project using the NGS framework, you can use the prebuilt Docker image along with a Docker Compose configuration. This setup includes PHP, Nginx, MySQL, and SSH, and allows you to add additional PHP extensions during build time.

### 📄 Steps to Get Started

1. **Create a new project directory**
   This will be your project root:

   ```bash
   mkdir my-ngs-project
   cd my-ngs-project
   ```

---

### 2. **Create a `docker-compose.yml` file**

Inside your project's root directory, create a file named `docker-compose.yml` and copy the following content into it:

```yaml
version: "3.8"

services:
  app:
    image: ngsadminuser/ngs-php-framework:latest
    container_name: ngs-local
    build:
      args:
        # Provide additional PHP extensions here separated by spaces.
        ADDITIONAL_PHP_EXTENSIONS: "opcache xdebug"
    ports:
      - "8080:80"      # Web access: http://localhost:8080
      - "3307:3306"    # MySQL
      - "2222:22"      # SSH
    volumes:
      - ./:/var/www/html  # Maps the current directory to /var/www/html inside the container
      - mysql-data:/var/lib/mysql  # Persist MySQL data
    networks:
      - web
    environment:
      - MYSQL_ROOT_PASSWORD=root
      - MYSQL_DATABASE=ngs
      - MYSQL_USER=user
      - MYSQL_PASSWORD=secret
      - PHP_VERSION=8.2
      - XDEBUG_ENABLED=0

networks:
  web:
    driver: bridge

volumes:
  mysql-data:
```

---

### ⚙️ **Alternative: Using Git Submodule**

Instead of manually creating the file, you can include it directly from a shared repository:

```bash
git submodule add https://stash.naghashyan.com/scm/ngs/ngs-docker-compose.git docker-compose
```

### ⚠️ **Additional Note:**

If you're checking out the main project from Git, the submodule directory will initially be empty. To fetch the contents of the submodule, you must explicitly initialize and update it with the following command:

```bash
git submodule update --init --recursive
```

After running this command, the submodule's content will become available within your project's directory structure.

---


### 🚀 **Extending the Docker Compose configuration**

To customize the Docker Compose setup specifically for your individual project, create a file named `docker-compose.override.yml` in your project's root directory. Docker Compose automatically merges this file with the main `docker-compose.yml`, allowing you to override or extend any settings.

**Example**: Create `docker-compose.override.yml` to customize your project:

```yaml
version: "3.8"

services:
  app:
    ports:
      - "9090:80"           # Override default web port
    environment:
      MYSQL_PASSWORD: test123   # Override MySQL user password
      XDEBUG_ENABLED: 1         # Enable Xdebug for debugging
      MYSQL_DATABASE: my_project_db  # Custom database name

volumes:
  mysql-data:
    name: my_project_mysql_data  # Custom named volume per project
```

### 📌 **Running the combined configuration (PowerShell script):**

A PowerShell script named `Start-Compose.ps1` is already included in your Docker Compose template repository (`ngsdocker-compose`). This script automatically detects if an override file exists in your project's main directory and runs Docker Compose accordingly.

#### **Executing the PowerShell script:**

Run the script directly from your project root:

```powershell
.\ngs-docker-compose\start-compose.ps1
```

This will launch your Docker environment, automatically applying any overrides you've defined.

### 🗑️ **Removing MySQL Volume (PowerShell script):**

A PowerShell script named `Remove-MySQL-Volume.ps1` is also included in the Docker Compose template repository. This script safely removes the Docker volume associated with your project's MySQL data. Use this command if you need to reset your MySQL database:

#### **Executing the PowerShell script to remove MySQL volume:**

Run the script directly from your project root:

```powershell
.\ngs-docker-compose\remove-mysql-volume.ps1
```

---

This structured approach ensures consistency across projects using NGS, providing flexibility for project-specific needs through straightforward customization with Docker Compose overrides and PowerShell automation.


3. **Start the Docker environment**

   Run the following command in the root of your project:

   ```bash
   docker-compose up -d
   ```

   This will build the image (if needed) and launch your containers in the background.

4. **Access your project**

    * Open [http://localhost:8080](http://localhost:8080) in your browser to see your app.
    * Use port **3307** for connecting to MySQL.
    * SSH access is available at **localhost:2222**.

### ⚙️ Optional Customization

* **Add extra PHP extensions**
  You can modify the `ADDITIONAL_PHP_EXTENSIONS` build argument to install any extra extensions your project needs.

  Example:

  ```yaml
  ADDITIONAL_PHP_EXTENSIONS: "opcache xdebug mysqli intl"
  ```

* **Customize environment variables**
  Change database credentials or PHP version using the `environment` section in the Compose file.

### 📌 Setting Up a Project via Composer in Docker Environment

To set up your NGS project using Composer within the Docker environment, follow these steps:

1. **Connect to Docker container terminal**:

   ```bash
   docker exec -it ngs-local bash
   ```

2. **Navigate to project directory**:

   ```bash
   cd /var/www/html
   ```

3. **Initialize the project with Composer**:

   ```bash
   composer init
   composer require naghashyan/ngs-php-framework
   composer install
   ```

4. **Verify your installation**:

   Ensure that your composer.json now contains the NGS framework dependency:

   ```json
   "require": {
     "naghashyan/ngs-php-framework": "^latest"
   }
   ```

Now your project is ready to start using NGS within your Docker environment.

By following these steps, each new NGS project will have a consistent, self-contained Docker-based setup — no need to install PHP, MySQL, or Nginx locally.
