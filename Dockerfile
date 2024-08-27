FROM php:8-apache
COPY . /usr/src/docket
ENV APACHE_DOCUMENT_ROOT=/usr/src/docket/htdocs
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
WORKDIR /usr/src/docket
