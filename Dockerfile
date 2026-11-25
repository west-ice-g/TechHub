# TechHub Docker image: PHP 8.2 + Apache
FROM php:8.2-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php.ini /usr/local/etc/php/conf.d/techhub.ini
COPY docker/apache/techhub.conf /etc/apache2/conf-available/techhub.conf
RUN a2enconf techhub \
    && sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

COPY --chown=www-data:www-data . /var/www/html
COPY docker/entrypoint.sh /usr/local/bin/techhub-entrypoint
RUN chmod 0755 /usr/local/bin/techhub-entrypoint

WORKDIR /var/www/html
ENTRYPOINT ["techhub-entrypoint"]
CMD ["apache2-foreground"]
