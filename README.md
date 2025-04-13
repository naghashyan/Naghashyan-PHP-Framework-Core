# What is NGS

1.  What is NGS Best for?
2.  How does NGS Compare with Other Frameworks?
3.  Requirements and Prerequisites

NGS (Naghashyan Framework) is a high performance, component-based PHP framework for rapidly developing modern Web applications. 

is NGS Best for?

NGS is a generic Web programming framework, meaning that it can be used for developing all kinds of Web applications using PHP. Because of its component-based architecture and sophisticated caching support, it is especially suitable for developing large-scale applications such as portals, forums, content management systems (CMS), e-commerce projects, RESTful Web services, and so on.

## How does NGS Compare with Other Frameworks?

If you're already familiar with another framework, you may appreciate knowing how NGS compares:

*   Like most PHP frameworks, NGS implements the MVC (Model-View-Controller) architectural pattern and promotes code organization based on that pattern.
*   NGS takes the philosophy that code should be written in a simple yet elegant way. NGS will never try to over-design things mainly for the purpose of strictly following some design pattern.
*   NGS is a full-stack framework providing many proven and ready-to-use features: query builders and ActiveRecord for both relational and NoSQL databases; RESTful API development support; multi-tier caching support; and more.
*   NGS is extremely extensible. You can customize or replace nearly every piece of the core's code. You can also take advantage of NGS's solid extension architecture to use or develop redistributable extensions.
*   High performance is always a primary goal of NGS.

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

2. **Create a `docker-compose.yml` file**  
   Inside your project root, create a file named `docker-compose.yml` and copy the following content into it:

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
   ```

3. **Start the Docker environment**

   Run the following command in the root of your project:

   ```bash
   docker-compose up -d
   ```

   This will build the image (if needed) and launch your containers in the background.

4. **Access your project**

    - Open [http://localhost:8080](http://localhost:8080) in your browser to see your app.
    - Use port **3307** for connecting to MySQL.
    - SSH access is available at **localhost:2222**.

---

### ⚙️ Optional Customization

- **Add extra PHP extensions**  
  You can modify the `ADDITIONAL_PHP_EXTENSIONS` build argument to install any extra extensions your project needs.

  Example:

  ```yaml
  ADDITIONAL_PHP_EXTENSIONS: "opcache xdebug mysqli intl"
  ```

- **Customize environment variables**  
  Change database credentials or PHP version using the `environment` section in the Compose file.

---

By following these steps, each new NGS project will have a consistent, self-contained Docker-based setup — no need to install PHP, MySQL, or Nginx locally.
