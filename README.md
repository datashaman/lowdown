# Lowdown

**Lowdown** will generate documentation with as much detail as you provide in the form of type hints
and PHPDoc annotations or _docblocks_ within your project.

All _docblocks_ are run through a _Markdown_ parser.

**Lowdown** pays special attention to functions and methods which include a `<pre></pre>` block in the _docblock_.

Whatever is within the `<pre></pre>` tag is deemed to be a code example.

The example is extracted and evaluated dynamically; the output is saved for inclusion in the documentation.

To further sweeten the deal, the example code can be posted to _GitHub_ as a gist.

A link to run it as a [Melody](http://melody.sensiolabs.org) script is generated with the documentation.

## install

Install the composer package into your project as a dev dependency:

    composer require --dev datashaman/lowdown

## configure

Add environment variables to your _.env_ to configure the build process. These are all optional.

* `LOWDOWN_DEST`
  The destination folder where documentation is written to. Defaults to _docs/api_.
* `LOWDOWN_GISTS_CACHED`
  _GitHub Gist_ requests should be cached. Defaults to _true_.
* `LOWDOWN_GISTS_TOKEN`
  _GitHub_ personal access token. Used for generating _Gists_.
* `LOWDOWN_GISTS_USERNAME`
  _GitHub_ username. Used for generating _Gists_.
* `LOWDOWN_SOURCES`
  The source folders where documentation is generated from. Comma-delimited. Defaults to _app,src_.
* `LOWDOWN_WHITELIST`
  Namespace whitelist. If set, documentation will be generated for only these namespaces. Comma-delimited.

## build

Build your package's documentation:

    lowdown build

Build your package's documentation with _Gists_ included:

    lowdown build --gist

## serve

Serve your package's documentation:

    lowdown serve
