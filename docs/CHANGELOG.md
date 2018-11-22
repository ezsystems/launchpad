# eZ Launchpad

## CHANGELOG

### ?.?.?

- Add support for calling composer ezplatform-install (script) on "initialdata" argument


### 1.4.0

- Add SOLR_CORES env variable on solr container to define the core(s) name. (default = "collection1")
- Add DATABASE_PREFIXES env variable on engine container. This var define the prefix used to name the db connection vars (default = "DATABASE"). It's possible to define multiple prefixes to handle multiple db.
- Remove composer.phar manipulation and add composer in the engine image.

### 1.3.0

- First version ready for 2.x with the new directory structure 

### 1.2.1 

- Apply EZP-28183 change

### 1.2.0 

- use Redis for Session for Platform.sh configuration by default
- Wizard Simplification, to remove the questions in 'standard' mode
- Fix TCP port mapping with Memached Admin

### 1.1.0 

- ezplatform-http-cache is enabled when not loaded
- Varnish5 and xkey VCL by default
- new Default README.md after initialize
- Simpler Makefile
- Cache are now purged when using Varnish (new ENV var trigger ezplatform to do it)
- Platform.sh optimizations
- Docker-Compose file is more readable
- Fix #7
- Fix #8


### 1.0.0 

- Initial Stable Version
