<?php

namespace Behat\Behat\Tester;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Symfony\Component\EventDispatcher\Event;

use Behat\Gherkin\Node\NodeVisitorInterface,
    Behat\Gherkin\Node\AbstractNode,
    Behat\Gherkin\Node\OutlineNode;

use Behat\Behat\Event\OutlineEvent,
    Behat\Behat\Event\OutlineExampleEvent;

/*
 * This file is part of the Behat.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Outline tester.
 *
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class OutlineTester extends ScenarioTester
{
    /**
     * Service container.
     *
     * @var     Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;
    /**
     * Event dispatcher.
     *
     * @var     Behat\Behat\EventDispatcher\EventDispatcher
     */
    protected $dispatcher;

    /**
     * Initializes tester.
     *
     * @param   Symfony\Component\DependencyInjection\ContainerInterface    $container  service container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container    = $container;
        $this->dispatcher   = $container->get('behat.event_dispatcher');
    }

    /**
     * Visits & tests OutlineNode.
     *
     * @param   Behat\Gherkin\Node\AbstractNode $outline
     *
     * @return  integer
     */
    public function visit(AbstractNode $outline)
    {
        $this->dispatcher->dispatch('beforeOutline', new OutlineEvent($outline));

        $result = 0;

        // Run examples of outline
        foreach ($outline->getExamples()->getHash() as $iteration => $tokens) {
            $itResult = $this->visitOutlineExample($outline, $iteration, $tokens);

            $result = max($result, $itResult);
        }

        $this->dispatcher->dispatch('afterOutline', new OutlineEvent($outline, $result));

        return $result;
    }

    /**
     * Visits & tests OutlineNode example.
     *
     * @param   Behat\Gherkin\Node\OutlineNode  $outline
     * @param   integer                         $row        row number
     * @param   array                           $tokens     step replacements for tokens
     *
     * @return  integer
     */
    protected function visitOutlineExample(OutlineNode $outline, $row, array $tokens = array())
    {
        $environment    = $this->container->get('behat.environment_builder')->build();
        $itResult       = 0;
        $skip           = false;

        $this->dispatcher->dispatch('beforeOutlineExample', new OutlineExampleEvent(
            $outline, $row, $environment
        ));

        // Visit & test background if has one
        if ($outline->getFeature()->hasBackground()) {
            $bgResult = $this->visitBackground($outline->getFeature()->getBackground(), $environment);
            if (0 !== $bgResult) {
                $skip = true;
            }
            $itResult = max($itResult, $bgResult);
        }

        // Visit & test steps
        foreach ($outline->getSteps() as $step) {
            $stResult = $this->visitStep($step, $environment, $tokens, $skip);
            if (0 !== $stResult) {
                $skip = true;
            }
            $itResult = max($itResult, $stResult);
        }

        $this->dispatcher->dispatch('afterOutlineExample', new OutlineExampleEvent(
            $outline, $row, $environment, $itResult, $skip
        ));

        return $itResult;
    }
}
