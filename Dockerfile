FROM php:8.3-cli

# Criar usuário não-root
RUN useradd -m -u 1000 phpuser

# Copiar aplicação
COPY --chown=phpuser:phpuser index.php /var/www/html/index.php

# Definir permissões
RUN chmod 755 /var/www/html && chmod 644 /var/www/html/index.php

# Switch para usuário não-root
USER phpuser

CMD ["php", "-S", "0.0.0.0:8089", "-t", "/var/www/html"]