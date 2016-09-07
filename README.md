RepoServiceBundle
=================
**FOR EXPERIMENTAL USE ONLY!**

The RepoServiceBundle provides the ability to declare a Doctrine repositories as a Symfony services.

Requirements
------------
- Symfony >= 3
- Doctrine >= 2.5
- PHP >= 5.6

Installation
------------
Require the bundle with the composer:
```
composer require davidkmenta/repo-service-bundle "dev-master"
```

Enable the bundle in the kernel:
```php
<?php
// app/AppKernel.php
 
public function registerBundles()
{
    $bundles = [
        // ...
        new DavidKmenta\RepoServiceBundle\RepoServiceBundle(),
        // ...
    ];
}
```

Documentation
-------------
- First of all, you have to create or update your repository class. A repository class has to extend the class `DavidKmenta\RepoServiceBundle\Repository\EntityRepository`.
- Now you have to implement a method `getMappedEntityName` which tells to the EntityManager what entity is managed by this repository. The best practise is, return a fully qualified class name:

```php
<?php

namespace AcmeBundle\Repository;

use AcmeBundle\Entity\CustomEntity;
use DavidKmenta\RepoServiceBundle\Repository\EntityRepository;
use Psr\Log\LoggerInterface;

class CustomRepository extends EntityRepository
{
    public function __construct(LoggerInterface $logger)
    {
        // ...
    }
    
    protected function getMappedEntityName()
    {
        return CustomEntity::class;
    }
}
```
- Last step is about defining the repository as a service. It's as simple as any other definition:

```yml
acme.repository.custom:
    class: AcmeBundle\Repository\CustomRepository
    arguments: ["@logger"]
    tags:
        - { name: doctrine.repository }
```
That's it! Yes, the trick is in the tag `doctrine.repository` :-) and the logger is injected as you're expecting.

TODOs and known issues
-----
- Instead of using the method `getMappedEntityName`, declare an entity name through a class annotation.
- Repositories can't be used as a value of entity's `repositoryClass` attribute.

Disclaimer
----------
Using this bundle is on own risk.

License
-------
MIT

Contributing
------------
Any contribution is welcomed :-)
