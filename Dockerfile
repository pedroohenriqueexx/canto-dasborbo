FROM php:8.2-apache

ARG CACHEBUST=3

RUN a2enmod rewrite

# Substituir VirtualHost padrão por um que permite .htaccess
RUN printf '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html\n\
    <Directory /var/www/html>\n\
        AllowOverride All\n\
        Options -Indexes +FollowSymLinks\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>\n' > /etc/apache2/sites-available/000-default.conf

COPY . /var/www/html/

RUN mkdir -p /var/www/html/api/transactions && \
    chmod 750 /var/www/html/api/transactions && \
    chown -R www-data:www-data /var/www/html/api/transactions

EXPOSE 80

CMD bash -c "rm -f /etc/apache2/mods-enabled/mpm_event.load \
    /etc/apache2/mods-enabled/mpm_event.conf \
    /etc/apache2/mods-enabled/mpm_worker.load \
    /etc/apache2/mods-enabled/mpm_worker.conf && \
    ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load && \
    ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf && \
    apache2-foreground"
