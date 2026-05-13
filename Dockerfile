FROM php:8.2-apache

# Desactivar MPMs conflituosos e activar apenas prefork
RUN a2dismod mpm_event || true && \
    a2dismod mpm_worker || true && \
    a2enmod mpm_prefork && \
    a2enmod rewrite

# Configurar AllowOverride via ficheiro dedicado (sem tocar no apache2.conf)
RUN printf '<Directory /var/www/html>\n    AllowOverride All\n    Options -Indexes +FollowSymLinks\n    Require all granted\n</Directory>\n' \
    > /etc/apache2/conf-available/htaccess.conf && \
    a2enconf htaccess

COPY . /var/www/html/

RUN mkdir -p /var/www/html/api/transactions && \
    chmod 750 /var/www/html/api/transactions && \
    chown -R www-data:www-data /var/www/html/api/transactions

EXPOSE 80
