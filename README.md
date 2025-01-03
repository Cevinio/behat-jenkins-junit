> [!CAUTION]
> This repository is a work in progress. DO NOT USE!

# Behat Jenkins JUnit formatter

This extension is a modification of the `JUnitFormatter` that is included in Behat.
This version will generate XML files that are compatible with the [Jenkins xUnit plugin](https://plugins.jenkins.io/xunit/).

The initial version of this library was based on the JUnit implementation of Behat `3.17.0`.

## 1. Installation

Require the package as a dev dependency via Composer.

```
composer require --dev cevinio/behat-jenkins-junit
```

## 2. Configuration

This extension adds a new formatter called `jjunit`.

Add the extension to your `behat.yml` configuration file:

```
default:
    extensions:
        Cevinio\BehatJenkinsJUnit\JJUnitFormatterExtension: ~
    formatters:
        jjunit:
            enabled: false
```

The available settings for the formatter:

| **name**      | **type**  | **default** | **description**                                                                                    |
|---------------|-----------|-------------|----------------------------------------------------------------------------------------------------|
| `enabled`     | `boolean` | `true`      | whether the formatter is enabled (ignored if a `--format` argument is present)                     |
| `output_path` | `string`  |             | the path to write the XML files to (required, or alternatively use `--format jjunit --out <path>`) |
| `prefix`      | `string`  |             | prefix for the generated files                                                                     |
| `suffix`      | `string`  |             | suffix for the generated files                                                                     |
| `success`     | `boolean` | `true`      | include successful scenario's in the output file                                                   |

## 3. Usage

Use the `--format` option to start generating the JUnit XML files:
```
vendor/bin/behat --format jjunit --out <path/to/dir>
```

Note that the output destination must be a directory, as Behat will generate an XML file per suite.
This is identical to how the native JUnit formatter works.
