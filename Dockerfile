FROM php:8.3-cli

RUN docker-php-ext-install sockets
RUN curl -LO https://github.com/DataDog/dd-trace-php/releases/latest/download/datadog-setup.php \
    && php datadog-setup.php --php-bin=all \
    && rm datadog-setup.php

WORKDIR /var/www/html

COPY index.php .

EXPOSE 8089

CMD ["php", "-S", "0.0.0.0:8089", "index.php"]