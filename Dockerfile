FROM php:8.2-apache

# Forçar rebuild (incrementar este número para invalidar cache)
ARG CACHEBUST=1

# Remover TODOS os ficheiros MPM e activar apenas prefork
RUN find /etc/apache2/mods-enabled/ -name "mpm_*.load" -o -name "mpm_*.conf" | xargs rm -f && \
    ln -s /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load && \
    ln -s /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf && \
    a2enmod rewrite

# Configurar AllowOverride via ficheiro dedicado
RUN printf '<Directory /var/www/html>\n    AllowOverride All\n    Options -Indexes +FollowSymLinks\n    Require all granted\n</Directory>\n' \
    > /etc/apache2/conf-available/htaccess.conf && \
    a2enconf htaccess

COPY . /var/www/html/

RUN mkdir -p /var/www/html/api/transactions && \
    chmod 750 /var/www/html/api/transactions && \
    chown -R www-data:www-data /var/www/html/api/transactions

EXPOSE 80
