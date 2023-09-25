# Installation

```console
composer require phariscope/event-store
```

# Usage

No direct usage. Just use this package if you want to develop your own event storage component.

To develop your own storage:
* implement StoreInteface
* create your subscriber extending PersistEventSubscriberAbstract
* register your subscriber when needed

A sample of StoreInterface is given StoreEventInMemory. You can use it for tests purposes

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

with Test Developpment Driven (thanks Kent Beck and the others), good practices (thanks R.Martin and the others)

## Quality

* phpcs PSR12
* phpstan level 9
* coverage 100%
* infection MSI >99%

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