<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_bazi
 *
 * @copyright   Copyright (C) 2026 Yutang2049
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Extension\Service\Provider\CategoryFactory;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * The BaZi site service provider.
 *
 * @since  1.0.0
 */
return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new CategoryFactory('\\Yutang\\Component\\Bazi'));
        $container->registerServiceProvider(new MVCFactory('\\Yutang\\Component\\Bazi'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Yutang\\Component\\Bazi'));
        $container->registerServiceProvider(new RouterFactory('\\Yutang\\Component\\Bazi'));

        $container->set(
            \Yutang\Component\Bazi\Site\Service\BaziCalculator::class,
            function (Container $container) {
                return new \Yutang\Component\Bazi\Site\Service\BaziCalculator();
            }
        );

        $container->set(
            \Yutang\Component\Bazi\Site\Service\AntiSpamService::class,
            function (Container $container) {
                return new \Yutang\Component\Bazi\Site\Service\AntiSpamService();
            }
        );
    }
};
