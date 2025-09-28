FROM php:8.2-alpine
COPY . /var/www/html
WORKDIR /var/www/html/public
EXPOSE 80
CMD ["php", "-S", "0.0.0.0:80", "router.php"]
