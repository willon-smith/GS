# Commodities Good Steward: production image (PHP 8.3 + Apache).
# Used by Render (see render.yaml) and works with any Docker host:
#   docker build -t cgs . && docker run -p 8080:80 cgs

FROM php:8.3-apache

# GD for product photo resizing; pdo_sqlite, sqlite3 and fileinfo are built in.
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      libpng16-16 libjpeg62-turbo libwebp7 libfreetype6 \
      libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev \
 && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
 && docker-php-ext-install -j"$(nproc)" gd \
 && apt-get purge -y libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev \
 && apt-get autoremove -y \
 && rm -rf /var/lib/apt/lists/*

# Apache modules the .htaccess relies on, plus production php.ini defaults.
RUN a2enmod rewrite headers expires \
 && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && sed -i 's/^upload_max_filesize.*/upload_max_filesize = 8M/; s/^post_max_size.*/post_max_size = 10M/' "$PHP_INI_DIR/php.ini"

COPY --chown=www-data:www-data . /var/www/html/
COPY docker/entrypoint.sh /usr/local/bin/cgs-entrypoint
RUN chmod +x /usr/local/bin/cgs-entrypoint \
 && rm -rf /var/www/html/docker \
 && mkdir -p /var/www/html/data /var/www/html/uploads \
 && chown -R www-data:www-data /var/www/html/data /var/www/html/uploads

ENV APP_ENV=production
EXPOSE 80
ENTRYPOINT ["cgs-entrypoint"]
CMD ["apache2-foreground"]
