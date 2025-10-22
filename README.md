# DDEV Symfony Template

## Stack

- PHP 8.3
- Symfony 7
- Postgres 16
- Node LTS

## Symfony configuration

- Doctrine
- Form
- Security
- Mercure
- Twig
- Webpack (+ Stimulus)
- Maker
- Web Profiler

## Good to knows

Before start, rename `ddev-symfony`  occurrences with the desired project name. These occurrences can be found in the following files:

- .ddev/config.yaml
- .ddev/docker-compose.mercure.yaml
- .env

⚠️ Do not change the DATABASE_URL, it's the default database configuration

## Build

In the project directory:

````bash
ddev start
````

Then the containers is running (you can check with `ddev describe`)

````bash
ddev ssh
````

And within the container:

````bash
composer i
npm i
npm run build
````
