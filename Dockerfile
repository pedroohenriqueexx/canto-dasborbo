FROM php:8.2-apache

# Corrigir conflito de MPM e activar mod_rewrite
RUN a2dismod mpm_event mpm_worker 2>/dev/null; \
    a2enmod mpm_prefork rewrite

# Permitir .htaccess em todo o projecto
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copiar os ficheiros do projecto
COPY . /var/www/html/

# Criar pasta de transações com permissões correctas
RUN mkdir -p /var/www/html/api/transactions && \
    chmod 750 /var/www/html/api/transactions && \
    chown -R www-data:www-data /var/www/html/api/transactions

EXPOSE 80
