# Project Setup Instructions
## Table of Contents
- [Project Overview](#project-overview)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Running the Project](#running-the-project)
- [Usage](#usage)
- [Contributing](#contributing)
- [License](#license)
## Project Overview
This the sendit adminworld backend 
## Installation
Follow these steps to set up your project:
1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd <repository-name>

## FRIST RUN THE THAT IS IN PACKAGE.JSON USER SCRIPT TO BUILD AND WATCH

2. **Setup the project**;
composer install or composer update
php bin/console secret:generate
npm install -g @symfony/webpack-encore --save-dev
npx encore production --progress
npm install admin-lte@^3.1.0 --save
npm install bootstrap@^4.6.2 --save


## when the build was failed then run the following command to set the node options for globaly

export NODE_OPTIONS=--openssl-legacy-provider

## cache clear command

php bin/console cache:clear