# eZ Launchpad

eZ Launchpad is a CLI tool to start an eZ Platform project in 5 min on top of a full Docker stack.

You can find the full documentation here: https://ezsystems.github.io/launchpad

| Branch   | Travis build status |
|:--------:|:-------------------:|
| master   | [![Build Status](https://travis-ci.org/ezsystems/launchpad.svg?branch=master)](https://travis-ci.org/ezsystems/launchpad)

## Contribution

This project comes with Coding Standards and Tests.
To help you contribute a Makefile is available to simplify the actions.

```bash
$ make
eZ Launchpad available targets:
  codeclean    > run the codechecker
  tests        > run the tests
  coverage     > generate the code coverage
  install      > install vendors
  clean        > removes the vendors, caches, etc.
  phar         > build the phar locally into your home
```

Please comply with `make codeclean` and `make tests` before to push, your PR won't be merged otherwise.

> Note: the real *signed* .phar is generated on Travis and made available for all on Github after each merge on master.
> Then there is no reason when you contribute to commit the .phar, it will be overriden at the merge. 

## License

This project is under the MIT license. See the complete license in the file:

[LICENSE](LICENSE)



