<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pentarim\SyliusAffiliateBundle\Form\EventListener;

use Sylius\Component\Registry\ServiceRegistryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * @author Laszlo Horvath <pentarim@gmail.com>
 * @author Arnaud Langlade <arn0d.dev@gmail.com>
 */
abstract class AbstractConfigurationSubscriber implements EventSubscriberInterface
{
    /**
     * @var ServiceRegistryInterface
     */
    protected $registry;
    /**
     * @var FormFactoryInterface
     */
    protected $factory;
    /**
     * @var string
     */
    protected $registryIdentifier;

    public function __construct(
        ServiceRegistryInterface $provisionRegistry,
        FormFactoryInterface $factory,
        $registryIdentifier = null
    ) {
        $this->registry = $provisionRegistry;
        $this->factory = $factory;
        $this->registryIdentifier = $registryIdentifier;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA  => 'preSetData',
            FormEvents::POST_SET_DATA => 'postSetData',
            FormEvents::PRE_SUBMIT    => 'preSubmit',
        );
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $provision = $event->getData();

        if (null === $type = $this->getRegistryIdentifier($provision)) {
            return;
        }

        $this->addConfigurationFields($event->getForm(), $type, $this->getConfiguration($provision));
    }

    /**
     * @param FormEvent $event
     */
    public function postSetData(FormEvent $event)
    {
        $provision = $event->getData();

        if (null === $type = $this->getRegistryIdentifier($provision)) {
            return;
        }

        $event->getForm()->get('type')->setData($type);
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();

        if (empty($data) || !array_key_exists('type', $data)) {
            return;
        }

        $this->addConfigurationFields($event->getForm(), $data['type']);
    }

    /**
     * @param FormInterface $form
     * @param string        $registryIdentifier
     * @param array         $data
     */
    protected function addConfigurationFields(FormInterface $form, $registryIdentifier, array $data = array())
    {
        $model = $this->registry->get($registryIdentifier);

        $configurationField = $this->factory->createNamed(
            'configuration',
            $model->getConfigurationFormType(),
            $data,
            array(
                'auto_initialize' => false,
                'label'           => false,
            )
        );

        $form->add($configurationField);
    }

    /**
     * Return the identifier of the rule/provision registered in the registry
     *
     * @return string
     */
    abstract protected function getRegistryIdentifier($model);

    /**
     * Return the rule/provision configuration
     *
     * @return array
     */
    abstract protected function getConfiguration($model);
}
