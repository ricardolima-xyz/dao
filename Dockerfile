# This is the Dockerfile to set up a development environment for ezydb
#
# - Building the docker image: 
#   docker build -t ezydb .
#
# - Running the image as a container (assuming code is located in ~/code/evt123):
#   docker run -v ~/code/ezydb/:/ezydb/ -it ezydb

# Base image and settings

FROM debian:12-slim

ARG PHPVERSION="8.2"
ENV BASEFOLDER="/ezydb"

# Creating folder structure and copying files

RUN mkdir ${BASEFOLDER}
COPY . ${BASEFOLDER}

# Instaling dependencies

RUN apt-get update 
RUN apt-get -y install curl software-properties-common wget

# Installing MariaDB

#RUN curl -LsS -O https://downloads.mariadb.com/MariaDB/mariadb_repo_setup
#RUN bash mariadb_repo_setup --mariadb-server-version=11.5
#RUN apt-get update
#RUN apt-get -y install mariadb-server mariadb-client
#RUN rm mariadb_repo_setup

#RUN service mariadb start && \
#    mariadb -uroot -e "CREATE USER '${DBUSER}' IDENTIFIED BY '${DBPASSWORD}';" && \
#    mariadb -uroot -e "CREATE DATABASE ${DBNAME};" && \
#    mariadb -uroot -e "USE ${DBNAME}; GRANT ALL PRIVILEGES ON * . * TO '${DBUSER}';"

# Installing Postgres

#RUN apt-get -y install postgresql postgresql-contrib

#RUN service postgresql start && \
#    echo "CREATE ROLE ${DBUSER} LOGIN ENCRYPTED PASSWORD '${DBPASSWORD}';" | su postgres -c "psql" && \
#    su postgres -c "createdb ${DBNAME} --owner ${DBUSER}"

# Installing Sqlite3

RUN apt-get -y install sqlite3

# Installing PHP

RUN apt-get -y install apt-transport-https lsb-release ca-certificates curl unzip
RUN curl -sSLo /usr/share/keyrings/deb.sury.org-php.gpg https://packages.sury.org/php/apt.gpg
RUN sh -c 'echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list'
RUN apt-get update
RUN apt-get -y install php${PHPVERSION} libapache2-mod-php${PHPVERSION} php${PHPVERSION}-curl php${PHPVERSION}-dom php${PHPVERSION}-gd php${PHPVERSION}-intl php${PHPVERSION}-mbstring php${PHPVERSION}-mysql php${PHPVERSION}-pgsql php${PHPVERSION}-sqlite php${PHPVERSION}-zip

# Installing PHP Composer

RUN wget -O composer-setup.php https://getcomposer.org/installer
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN rm composer-setup.php


# Creating startup script - Starting services and using tail so the container does not exit

#RUN echo "service mariadb start" >> /startup.sh
#RUN echo "service postgresql start" >> /startup.sh
RUN echo "tail -f /var/log/apache2/access.log -f /var/log/apache2/error.log" >> /startup.sh

# Running startup script and exposing ports

CMD ["/bin/bash", "/startup.sh"]
EXPOSE 80 