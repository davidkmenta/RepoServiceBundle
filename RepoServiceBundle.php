<?php

namespace DavidKmenta\RepoServiceBundle;

use DavidKmenta\RepoServiceBundle\DependencyInjection\Compiler\RepositoryServicePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class RepoServiceBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new RepositoryServicePass());
    }
}
