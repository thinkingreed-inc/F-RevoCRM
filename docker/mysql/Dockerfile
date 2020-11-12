FROM mysql:8.0
RUN mkdir /var/log/mysql
RUN chown mysql:mysql /var/log/mysql
ADD ./docker/mysql/my.cnf /etc/mysql/conf.d/my.cnf
RUN chmod 644 /etc/mysql/conf.d/my.cnf