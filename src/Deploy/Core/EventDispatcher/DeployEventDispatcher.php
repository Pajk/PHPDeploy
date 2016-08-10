<?php

namespace Deploy\Core\EventDispatcher;

use Deploy\Core\Event\DeployEvents;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DeployEventDispatcher implements EventDispatcherInterface
{
    private $dispatcher;
    private $wrappedListeners;
    private $id;
    private $undo_priority;

    /**
     * Constructor.
     *
     * @param ContainerAwareEventDispatcher $dispatcher
     */
    public function __construct(ContainerAwareEventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->wrappedListeners = array();
        $this->rollback_priority = 0;
    }

    /**
     * {@inheritDoc}
     */
    public function addListener($eventName, $listener, $priority = 0)
    {
        $this->dispatcher->addListener($eventName, $listener, $priority);
    }

    /**
     * @param     $eventName
     * @param     $listener
     * @param int $priority
     */
    public function addListenerService($eventName, $listener, $priority = 0)
    {
        $this->dispatcher->addListenerService($eventName, $listener, $priority);
    }

    /**
     * {@inheritdoc}
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        $this->dispatcher->addSubscriber($subscriber);
    }

    /**
     * @param $serviceId
     * @param $class
     */
    public function addSubscriberService($serviceId, $class)
    {
        $this->dispatcher->addSubscriberService($serviceId, $class);
    }

    /**
     * {@inheritdoc}
     */
    public function removeListener($eventName, $listener)
    {
        $this->dispatcher->removeListener($eventName, $listener);
    }

    /**
     * {@inheritdoc}
     */
    public function removeSubscriber(EventSubscriberInterface $subscriber)
    {
        $this->dispatcher->removeSubscriber($subscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function getListeners($eventName = null)
    {
        return $this->dispatcher->getListeners($eventName);
    }

    /**
     * {@inheritdoc}
     */
    public function getListenerPriority($eventName, $listener )
    {
        return $this->dispatcher->getListenerPriority($eventName, $listener);
    }

    /**
     * {@inheritdoc}
     */
    public function hasListeners($eventName = null)
    {
        return $this->dispatcher->hasListeners($eventName);
    }

    /**
     * Proxies all method calls to the original event dispatcher.
     *
     * @param string $method    The method name
     * @param array  $arguments The method arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return call_user_func_array(array($this->dispatcher, $method), $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($eventName, Event $event = null)
    {
        if (null === $event) {
            $event = new Event();
        }

        $this->id = spl_object_hash($event);

        $this->preDispatch($eventName, $event);

        $this->dispatcher->dispatch($eventName, $event);

        // reset the id as another event might have been dispatched during the dispatching of this event
        $this->id = spl_object_hash($event);

        $this->postDispatch($eventName, $event);

        return $event;
    }

    private function preDispatch($eventName, Event $event)
    {
        // wrap all listeners before they are called
        $this->wrappedListeners[$this->id] = new \SplObjectStorage();

        $listeners = $this->dispatcher->getListeners($eventName);

        foreach ($listeners as $listener) {
            $this->dispatcher->removeListener($eventName, $listener);
            $wrapped = $this->wrapListener($eventName, $listener);
            $this->wrappedListeners[$this->id][$wrapped] = $listener;
            $this->dispatcher->addListener($eventName, $wrapped);
        }
    }

    private function postDispatch($eventName, Event $event)
    {
        foreach ($this->wrappedListeners[$this->id] as $wrapped) {
            $this->dispatcher->removeListener($eventName, $wrapped);
            $this->dispatcher->addListener($eventName, $this->wrappedListeners[$this->id][$wrapped]);
        }

        unset($this->wrappedListeners[$this->id]);
    }
    
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    public function getUndoPriority()
    {
        return $this->undo_priority++;
    }

    private function wrapListener($eventName, $listener)
    {
        $self = $this;

        return function (Event $event) use ($self, $eventName, $listener) {

            $self->getDispatcher()->addListener(DeployEvents::FAILED, array($listener[0], $listener[1] . 'Undo'), $self->getUndoPriority());

            call_user_func($listener, $event);
        };
    }
}
