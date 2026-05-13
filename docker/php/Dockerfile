FROM ubuntu:22.04

# Non-interactive for apt
ENV DEBIAN_FRONTEND=noninteractive

# Install dependencies
RUN apt-get update && apt-get install -y \
    software-properties-common \
    lsb-release \
    ca-certificates \
    curl \
    gnupg2

# Add PHP 8.2 repository
RUN add-apt-repository ppa:ondrej/php
# Install PHP & extensions
RUN apt-get update && apt-get install -y \
    php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml \
    php8.2-curl php8.2-zip php8.2-bcmath php8.2-gd \
    curl unzip git mariadb-client nginx supervisor

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer

# Replace default www.conf to use TCP port 9000
COPY www.conf /etc/php/8.2/fpm/pool.d/www.conf

WORKDIR /var/www

# Ensure PHP-FPM can write to /run/php
RUN mkdir -p /run/php

# Overwrite FPM pool config
COPY www.conf /etc/php/8.2/fpm/pool.d/www.conf

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm8.2", "-F"]