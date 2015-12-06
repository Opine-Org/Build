docker run -p 11211:11211 --name opine-memcached -d memcached:1.4.24
docker run -v /data/db --name opine-mongo-data -d tianon/true
docker run -p 27017:27017 --name opine-mongo --volumes-from opine-mongo-data -d mongo:2.6 --bind_ip=0.0.0.0