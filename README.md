# lowdown

Documentation generator for PHP projects. WIP.

## install

Install the composer package into your project as a dev dependency:

    composer require --dev datashaman/lowdown

## configure

Add environment variables to your _.env_ to configure the build process. These are all optional.

    LOWDOWN_DEST=docs/api
    LOWDOWN_GISTS_CACHED=true
    LOWDOWN_GISTS_TOKEN=12345678901234567890123456789012
    LOWDOWN_GISTS_USERNAME=username
    LOWDOWN_SOURCES=app,src
    LOWDOWN_WHITELIST=App

## build

Build your package's documentation:

    lowdown build

## serve

Serve your package's documentation:

    lowdown serve
