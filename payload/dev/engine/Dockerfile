FROM plopix/docker-php-ez-engine:7.3
MAINTAINER Plopix

COPY entrypoint.bash /entrypoint.bash
RUN chmod +x /entrypoint.bash
ENTRYPOINT ["/entrypoint.bash"]
CMD ["php-fpm"]
