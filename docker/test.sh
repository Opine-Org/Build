docker run \
    -e "OPINE_ENV=docker" \
    --rm \
    -v "$(pwd)/../":/app \
    opine:phpunit-build \
    --bootstrap /app/tests/bootstrap.php
