# Testing

## Github actions

All our packages have github actions by default, so you can test your [contribution](https://github.com/yiisoft/db-pgsql/blob/master/.github/CONTRIBUTING.md) in the cloud.

> Note: We recommend pull requesting in draft mode until all tests pass.

## Docker

For greater ease it is recommended to use docker containers.

You can use the [docker-compose.yml](https://docs.docker.com/compose/compose-file/) file with PostgreSQL 15
that is in the root of the package:

```dockerfile
docker-compose up -d
```

Or run container directly via command:

```dockerfile
docker run --rm --name yiisoft-db-pgsql-db -e POSTGRES_PASSWORD=root -e POSTGRES_USER=root -e POSTGRES_DB=yiitest -d -p 5432:5432 postgres:15
```

If you're running Docker on Linux (in WSL also), you can create [tmpfs volume](https://docs.docker.com/storage/tmpfs/)
that persist database in the host memory and significantly speeds up the execution time of tests. Use `docker run`
command argument for it:

```shell
--mount type=tmpfs,destination=/var/lib/postgresql/data
```

DB must be accessible by address `127.0.0.1`. If you use PHP via docker container, run PHP container in network of DB
container. Use `docker run` command argument for it:

```shell
 --network container:yiisoft-db-pgsql-db
```

## Unit testing

The package is tested with [PHPUnit](https://phpunit.de/).

The following steps are required to run the tests:

1. Run the docker container for the dbms.
2. Install the dependencies of the project with composer.
3. Run the tests.

```shell
vendor/bin/phpunit
```

- More information can be found in the [Internals.](../../internals.md)
