FROM php:8.2-apache

# Forçar apenas mpm_prefork — apagar directamente os outros MPMs
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load \
          /etc/apache2/mods-enabled/mpm_event.conf \
          /etc/apache2/mods-enabled/mpm_worker.load \
          /etc/apache2/mods-enabled/mpm_worker.conf && \
    a2enmod mpm_prefork rewrite

# Configurar AllowOverride via ficheiro dedicado
RUN printf '<Directory /var/www/html>\n    AllowOverride All\n    Options -Indexes +FollowSymLinks\n    Require all granted\n</Directory>\n' \
    > /etc/apache2/conf-available/htaccess.conf && \
    a2enconf htaccess

COPY . /var/www/html/

RUN mkdir -p /var/www/html/api/transactions && \
    chmod 750 /var/www/html/api/transactions && \
    chown -R www-data:www-data /var/www/html/api/transactions

EXPOSE 80
