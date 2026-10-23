# Project overview

This folder is the **API** of the budget app (see `../.claude/CLAUDE.md` for
the global presentation of the project). A separate front web app (`../front/`)
consumes it. This file only covers the API: there is no need to know more about
the front app here.

## Stack

REST/JSON-LD API: Symfony 7.4, API Platform, Doctrine ORM, PostgreSQL 16,
FrankenPHP (PHP 8.5). Everything runs in Docker: there is no local PHP, so run
commands with `docker compose exec php ...` from this folder
(e.g. `docker compose exec php bin/phpunit`).

# Learning context

This project is a learning project: the user is using it to learn Symfony
(and API Platform / Doctrine). The user is not a beginner: they already have
solid PHP foundations and experience with PHP frameworks. Skip basic PHP and
generic MVC/framework explanations, and focus on what is specific to Symfony
and its ecosystem.

- After implementing something, explain the Symfony concepts it relies on
  (e.g. constraints and validators, services and autowiring, state
  processors/providers, Doctrine Unit of Work, ...): what they are, why they
  fit here, and how the pieces connect.
- When relevant, point to the official documentation
  (https://symfony.com/doc, https://api-platform.com/docs,
  https://www.doctrine-project.org/projects/doctrine-orm/en/current/) with a
  link to the specific page, so the user can dig deeper.

# Coding guidelines

- Extract conditions into private methods with a descriptive name
  (`isNewlyPicked()`, `hasLeft()`, `isLastProfile()`, ...), so that the main
  method reads like a list of rules. See
  `src/Validator/Household/ValidRowOwnerMemberValidator.php` as the reference.
