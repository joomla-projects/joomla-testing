NOTE - This repository has been archived for now.

## GSOC 2017 Parallel Testing Project

### Summary
The project aims to reduce time of acceptance tests for Joomla extensions, with the initial focus on Weblinks.

This is made possible by running the tests in parallel in an environment of multiple Joomla servers and Selenium clients. The environment is automatically generated by the virtualisation project. Furthermore, a special algorithm has been designed and implemented to manage the optimal execution of the tasks, also taking account for their dependencies. 

### Run the parallel testing module

In order to run the tests, simply run the following command:

```
 vendor/bin/robo run:coordinator isacandrei weblinks container-test > coordinator.log 2>&1
```

The logs will be available on the coordinator.log file.

The extension acceptance tests logs are currently saved in the _output folder of the extension.

The current setup has 5 servers and 3 clients. Therefore the expected behaviour is to run the first three tests, and when one finishes, run the one from the remaining ones. When another one finishes, the last test will be run.
 
At the end, a statistics of the tasks on each server is stored in the logs, summarising how many tasks are completed successfully and how many are failed.

### Documentation

1. [Documentation](https://docs.joomla.org/Parallel_Testing)
 
2. [First Post](https://community.joomla.org/gsoc-2017/3127-parallel-testing-gsoc-2017.html)

3. [Second Post](https://docs.google.com/document/d/1SH-6h994C_X1CGuBK7WDZWLPScz9QpI1ZuYqShN-3aI/edit?usp=sharing)

4. [Meeting reports](https://volunteers.joomla.org/teams/gsoc-17-parallel-testing)


## Repositories List:
* [Weblinks](https://github.com/isacandrei/weblinks/tree/container-test)
* [Joomla Browser](https://github.com/isacandrei/joomla-browser/tree/container-test)
* [Virtualisation](https://github.com/isacandrei/virtualisation/tree/container-test)
* [Joomla Testing](https://github.com/isacandrei/joomla-testing/tree/container-test)
* [Joomla Testing Robo](https://github.com/isacandrei/joomla-testing-robo/tree/container-test)
* [Docker Joomla Automated Testing](https://github.com/isacandrei/docker-joomla-automated-testing/tree/container-test)

## PR Commits Lists:
* [Joomla Browser](https://github.com/joomla-projects/joomla-browser/pull/140/commits)
* [Virtualisation 1](https://github.com/joomla-projects/virtualisation/pull/13/commits)
* [Virtualisation 2](https://github.com/joomla-projects/virtualisation/pull/14/commits)
* [Virtualisation 3](https://github.com/joomla-projects/virtualisation/pull/15/commits)
* [Joomla Testing 1](https://github.com/joomla-projects/joomla-testing/pull/1/commits)
* [Joomla Testing 2](https://github.com/joomla-projects/joomla-testing/pull/4/commits)
* [Weblinks 1](https://github.com/jatitoam/weblinks/pull/2/commits)
* [Weblinks 2](https://github.com/jatitoam/weblinks/pull/3/commits)
* [Docker Joomla Automated Testing](https://github.com/joomla-projects/docker-joomla-automated-testing/pull/2/commits)
* [Joomla Testing Robo 1](https://github.com/joomla-projects/joomla-testing-robo/pull/10/commits)
* [Joomla Testing Robo 2](https://github.com/joomla-projects/joomla-testing-robo/pull/12/commits)

All the PRs have been accepted and merged into the public repositories. The project is ready to use and will be integrated with Travis for Joomla extensions. The only dependency needed for each extension is the yml file with the required tests and working acceptance tests, all being described in the documentation listed above.
