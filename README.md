# eZ Launchpad

eZ Launchpad is a CLI tool to start an eZ Platform project in 5 min on top of a full Docker stack.

Basically, when you have it, the CLI command will:

- ask you some questions to parameter your project
- build the Docker stack
- install composer in the stack
- install eZ Platform in the stack
- Start all the services required

For now there are:

- Nginx
- Maria DB
- PHP-FPM 7.1.x
- Memcache
- Memcache Admin
- Adminer
- Mailcatcher

## Install

### With 'curl'

`$ curl -LSs https://ezystems.github.io/ezlaunchpad/install_curl.bash | bash`

### With 'wget'

`$ wget -O - "https://ezystems.github.io/ezlaunchpad/install_wget.bash" | bash`

## Usage

`~/ez`

> For conveniency, you should install ez.phar somewhere in your PATH. `mv ~/ez SOMEWHERE_IN_YOUR_PATH`

## Documentation

You can find the full documentation here: https://ezsystems.github.io/ezlaunchpad#doc


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
```

Please comply with `make codeclean` and `make tests` before to push, your PR won't be merged otherwise.

> Note: the real *signed* .phar is generated on Travis and made available for all on Github after each merge on master.
> Then there is no reason when you contribute to commit the .phar, it will be overriden at the merge. 

## License

This project is under the MIT license. See the complete license in the file:

[LICENSE](LICENSE)



