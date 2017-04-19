# eZ Launchpad

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




