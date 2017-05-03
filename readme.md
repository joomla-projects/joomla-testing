# Joomla Automated Testing Platform

## What is this?
---------------------
* This is a Joomla Automated Testing Platform.
* Currently a work in process.


## Installing
---------------------

### Install Dependencies with Composer

```
composer install
```

In additon you have to copy a composer.phar into the project directory.

### Create the docker images and docker containers

Runs base servers

```
vendor/bin/robo run:servers
```

Basiclly this command runs the docker commands

```
docker pull mysql:latest
docker run -itd --name db --network joomla -e MYSQL_ROOT_PASSWORD=root -p 13306:3306 mysql:latest
```

```
docker pull joomla:3.6.5-apache-php7
docker run -itd --name php --network joomla -e JOOMLA_DB_HOST=db -e JOOMLA_DB_USER=root -e JOOMLA_DB_PASSWORD=root -e JOOMLA_DB_NAME=joomla -p 8080:80 joomla:3.6.5-apache-php7
```

So the ports 8080 and 13306 on your machine have to be unused.


### Delete the docker images and docker containers

Stops base servers

```
vendor/bin/robo stop:servers
```


### Run the tests

Starts tests of a given repository (Github based).
runServers MUST be executed first.

```
vendor/bin/robo run:tests repoOwner repoName repoBranch
```

If you want to run the test for weblinks (https://github.com/joomla-extensions/weblinks), use this command:

```
vendor/bin/robo run:tests joomla-extensions weblinks master
```

The current working project is

```
vendor/bin/robo run:tests jatitoam weblinks container-test
```

Basiclly this command runs the docker commands

```
docker pull joomlaprojects/joomla-testing-client-firefox:2.53.1
docker run -it --name client --network joomla -p 5901:5900 --volume=/home/astrid/docker/joomla/joomla-testing/.tmp/extension:/usr/src/tests:rw joomlaprojects/joomla-testing-client-firefox:2.53.1
```