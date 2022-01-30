FROM mysql:8.0
RUN mkdir /var/log/mysql
RUN chown mysql:mysql /var/log/mysql
ADD ./docker/mysql/my.cnf /etc/mysql/conf.d/my.cnf
RUN chmod 644 /etc/mysql/conf.d/my.cnf
RUN apt-key adv --keyserver hkp://keyserver.ubuntu.com --recv-keys 467B942D3A79BD29
RUN apt update
