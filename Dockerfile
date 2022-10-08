# Popular Dockerlamp base image with +1M pulls
FROM mattrayner/lamp:latest-2004-php8

ENV PHP_UPLOAD_MAX_FILESIZE 100M
ENV PHP_POST_MAX_SIZE 100M

RUN apt-get update && apt-get install -y \
    sudo curl ca-certificates \
    tar file xz-utils build-essential

# This is used to get files on and off the device easily.
RUN apt-get install -y magic-wormhole

# BEGIN REDIS PORTION
# Note this is a work-in-progress and phpFox v4 does not show
# that redis is working properly.
RUN apt-get install -y nodejs
# This results in an error because the target folder exists
# so disabled for now.
# RUN ln -s /usr/bin/nodejs /usr/bin/node
RUN apt-get install -y npm redis-server
RUN npm update && npm install
# END REDIS PORTION

# Image magic extension to allow uploads of different image types.
RUN apt-get install -y php8.0-imagick

# mattrayner/lamp image assumes /app as the web root
WORKDIR /app
COPY app .

# Per phpFox v4 documentation, the following is required
RUN chmod 777 PF.Base
RUN chmod 777 PF.Site

EXPOSE 3306
EXPOSE 80
# No CMD argument because the mattrayner/lamp image has it, which
# will run /app/index.php
