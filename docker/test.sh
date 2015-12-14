mongo --host 127.0.0.1 phpunit --eval 'db.createUser({"user": "unit", "pwd": "test", "roles": [{ "role": "readWrite", "db": "phpunit"}]});' > /dev/null 2>&1
docker run \
    -e "OPINE_ENV=docker" \
    --rm \
    --link opine-memcached:memcached \
    --link opine-mongo:mongo     \
    -v "$(pwd)/../":/app \
    opine:phpunit-build \
    --bootstrap /app/tests/bootstrap.php