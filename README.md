# Tech TV Development Environment
* PHP 8.1

# Docker backup and restore db
# Backup
docker exec ttv_db /usr/bin/mysqldump -u root --password=123456 ttv > config/backup/db/backup.sql

# Restore
cat config/backup/db/backup.sql | docker exec -i ttv_db /usr/bin/mysql -u root --password=123456 ttv

# Docker access
docker exec -it ttv /bin/bash

# Setup source
in web/sites
copy example.local.development.services.yml and rename to local.development.services.yml

in web/sites/default
copy example.settings.local.php and rename to settings.local.php

download docker from https://www.docker.com/
run docker-compose up -d
run cat config/backup/db/backup.sql | docker exec -i ttv_db /usr/bin/mysql -u root --password=123456 ttv
run docker exec -it ttv /bin/bash
run COMPOSER_PROCESS_TIMEOUT=2000 composer install && drush cr && drush cim -y
create folder config/keys and generate keys in /admin/config/people/simple_oauth
Access web https://localhost:8106 | http://localhost:80
Access phpmyadmin http://localhost:8105

# Account
admin
123456

# Setup api
client_id: 53e3332f-2a75-4492-9a6c-a92bdc37561b
client_secret: encPajhJR4nQgpA5EkfL6XJL0ijYkTd2

# Generate product in https://ttv-local.com:8109/admin/config/development/generate/product
Select furniture, in tab user select member then click generate