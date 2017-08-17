FROM plopix/docker-php-ez-engine:7
MAINTAINER Plopix

COPY entrypoint.bash /entrypoint.bash
RUN chmod +x /entrypoint.bash
ENTRYPOINT ["/entrypoint.bash"]
CMD ["php-fpm"]

WORKDIR /var/www/html/project/ezplatform/
