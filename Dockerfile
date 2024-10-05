FROM php:8.3-apache

RUN apt-get update && apt-get install -y git libzip-dev unzip && \
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
  php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
  php composer-setup.php && \
  php -r "unlink('composer-setup.php');" && \
  mv composer.phar /usr/local/bin/composer && \
  (curl -sS https://get.symfony.com/cli/installer | bash -s -- --install-dir=/usr/local/bin) && \
  docker-php-ext-install pdo_mysql zip && \
  a2enmod rewrite
