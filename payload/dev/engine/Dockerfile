FROM plopix/docker-php-ez-engine:7.4
MAINTAINER Plopix

ENV XDEBUG_ENABLED=0

RUN mkdir /usr/local/etc/php/enable-xdebug

COPY xdebug.ini /usr/local/etc/php/enable-xdebug/99-xdebug.ini

COPY entrypoint.bash /entrypoint.bash
RUN chmod +x /entrypoint.bash
ENTRYPOINT ["/entrypoint.bash"]
CMD ["php-fpm"]
