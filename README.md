# Installation

```console
composer require phariscope/event-store
```

# Usage

There is no direct usage for this package. You should use this package only if you want to develop your own event storage component.

To develop your own storage:

1. Create your own Store implementing the StoreInterface.
2. Create your subscriber by extending PersistEventSubscriberAbstract, which can be constructed with your store.

A sample of the StoreInterface is provided with StoreEventInMemory. You can use it for testing purposes.

# To Contribut to pharsicope/Event

## Requirements

* docker
* git

## Install

* git clone git@github.com:phariscope/EventStore.git

## Unit test

```console
bin/phpunit
```

Using Test-Driven Development (TDD) principles (thanks to Kent Beck and others), following good practices (thanks to Uncle Bob and others) and the great book 'DDD in PHP' by C. Buenosvinos, C. Soronellas, K. Akbary

## Quality

* phpcs PSR12
* phpstan level 9
* coverage 100%
* infection MSI 100%

Quick check with:
```console
./codecheck
```

Check coverage with:
```console
bin/phpunit --coverage-html var
```
and view 'var/index.html' with your browser

Check infection with:
```console
bin/infection
```
and view 'var/infection.html' with your browser