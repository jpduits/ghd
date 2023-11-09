#!/bin/bash
#/home/jp/dev/ghdataset_docker
docker run -e TZ=Europe/Amsterdam --name mysqldb -v mysql-data:/var/lib/mysql -d mysqlimage
# echo IP of container
docker inspect mysqldb | grep IP

