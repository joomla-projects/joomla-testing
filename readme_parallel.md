### Run the parallel testing module

Make sure yoy have the Virtualisation repo at the same level as the Joomla-testing repo because it is loaded manually in the composer. Until the pull request is accepted use this fork, on the container-test branch: https://github.com/isacandrei/virtualisation/tree/container-test

```
"Joomla\\Virtualisation\\" : "../virtualisation/src/"
```

In order to run the tests, simply run the following command:

```
 vendor/bin/robo run:coordinator isacandrei weblinks container-test > coordinator.log 2>&1
```

The logs will be available on the coordinator.log file.

The extension acceptance tests logs are not accessible anywhere, they are currently not logged in the selenium container. This issue needs further investigation.

The current setup has 5 servers and 3 clients. Therefore the expected behaviour is to run the first three tests, and when one finishes, run the one from the remaining ones. When another one finishes, the last test will be run.
 
FYI, even installWeblinks task fails. Manually tested in the container it works, but in the automatic testing it fails.

The current behaviour is: 

1. The first three tasks are assigned.

2. One or two are finished(failed) and the rest are assigned. And that's it.

The expected behaviour is:

1. The first three tasks are assigned. 

2. When one is done, another one is assigned until the available tasks(5) are completed.

3. All the tasks finish.
