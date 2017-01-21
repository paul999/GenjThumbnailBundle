<?php
namespace Genj\ThumbnailBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Liip\ImagineBundle\DependencyInjection\Factory\Resolver\ResolverFactoryInterface;
use Symfony\Component\DependencyInjection\Reference;

class LocalAndCdnResolverFactory implements ResolverFactoryInterface
{
	public function addConfiguration(ArrayNodeDefinition $builder)
	{
		$builder
			->children()
				->scalarNode('web_root')->defaultValue('%kernel.root_dir%/../web')->cannotBeEmpty()->end()
				->scalarNode('cache_prefix')->defaultValue('media/cache')->cannotBeEmpty()->end()
				->booleanNode('use_cdn')->defaultFalse()->end()
				->scalarNode('cdn')->defaultNull()->end()
			->end()
		;
	}
	
	public function getName()
	{
		return 'localAndCdnResolver';
	}
	
	public function create(ContainerBuilder $container, $resolverName, array $config)
	{
		$resolverDefinition = new DefinitionDecorator('genj_thumbnail.cache.resolver.prototype.localAndCdnResolver');
		$resolverDefinition->replaceArgument(2, $config['web_root']);
		$resolverDefinition->replaceArgument(3, $config['cache_prefix']);
		$resolverDefinition->replaceArgument(4, $config['use_cdn']);
		$resolverDefinition->replaceArgument(5, new Reference($config['cdn']));
		$resolverDefinition->addTag('liip_imagine.cache.resolver', array(
			'resolver' => $resolverName,
		));
		$resolverId = 'liip_imagine.cache.resolver.'.$resolverName;

		$container->setDefinition($resolverId, $resolverDefinition);
		
		return $resolverId;
	}
}