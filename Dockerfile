FROM php:8.3-cli

RUN docker-php-ext-install sockets

WORKDIR /var/www/html

COPY index.php .

EXPOSE 8089

CMD ["php", "-S", "0.0.0.0:8089", "index.php"]