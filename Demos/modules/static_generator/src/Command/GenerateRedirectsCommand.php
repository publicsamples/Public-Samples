<?php

namespace Drupal\static_generator\Command;

use Drupal\static_generator\StaticGenerator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Annotations\DrupalCommand;


/**
 * Class GenerateRedirectsCommand.
 *
 * @DrupalCommand (
 *     extension="static_generator",
 *     extensionType="module"
 * )
 */
class GenerateRedirectsCommand extends ContainerAwareCommand {

  /**
   * The Static Generator service.
   *
   * @var \Drupal\static_generator\StaticGenerator
   */
  protected $staticGenerator;

  /**
   * GenPageCommand constructor.
   *
   * @param \Drupal\static_generator\StaticGenerator $static_generator
   */
  public function __construct(StaticGenerator $static_generator) {
    $this->staticGenerator = $static_generator;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('sg:generate-redirects')
      ->setDescription($this->trans('commands.sg.generate-blocks.description'))
      ->setAliases(['sgr']);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $elapsed_time = $this->staticGenerator->generateRedirects();
    $this->getIo()->info('Generate redirects completed, elapsed time: ' . $elapsed_time . ' seconds.');
//    $this->getIo()->info($this->trans('commands.sg.generate-blocks.messages.success'));
  }

}
