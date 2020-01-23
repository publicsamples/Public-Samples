<?php

namespace Drupal\static_generator\Command;

use Drupal\static_generator\StaticGenerator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Annotations\DrupalCommand;


/**
 * Class GenerateAllCommand.
 *
 * @DrupalCommand (
 *     extension="static_generator",
 *     extensionType="module"
 * )
 */
class GenerateAllCommand extends ContainerAwareCommand {

  /**
   * The Static Generator service.
   *
   * @var \Drupal\static_generator\StaticGenerator
   */
  protected $staticGenerator;

  /**
   * GenCommand constructor.
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
      ->setName('sg:generate-all')
      ->setDescription($this->trans('commands.sg.generate-pages.description'))
      ->setAliases(['sg'])
      ->addOption(
        'q',
        NULL,
        InputOption::VALUE_NONE,
        $this->trans('commands.sg.generate-all.options.q'));
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    // Get confirmation option.
    $confirmed = $input->getOption('yes');

    // Initiatlize answer var in case confirmation is not set.
    $answer = 'no';

    // If not confirmed with '--yes', ask.
    if (!$confirmed) {
      if (empty($input->getOption('q'))) {
        $answer = $this->getIo()
          ->ask('Delete and re-generate entire static site (yes/no)? ');
      }
      else {
        $answer = 'yes';
      }
    }

    if ($confirmed || strtolower($answer) == 'yes') {
      $elapsed_time = $this->staticGenerator->generateAll();
      $this->getIo()
        ->info('Full site static generation complete, elapsed time: ' . $elapsed_time . ' seconds.');
      //$this->getIo()->info($this->trans('commands.sg.generate-pages.messages.success'));
    }
  }
}
