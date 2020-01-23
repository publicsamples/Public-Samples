<?php

namespace Drupal\static_generator\Command;

use Drupal\static_generator\StaticGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Annotations\DrupalCommand;


/**
 * Class GenerateFilesCommand.
 *
 * @DrupalCommand (
 *     extension="static_generator",
 *     extensionType="module"
 * )
 */
class GenerateFilesCommand extends ContainerAwareCommand {

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
      ->setName('sg:generate-files')
      ->setDescription($this->trans('commands.sg.generate-files.description'))
      ->addOption(
        'public',
        NULL,
        InputOption::VALUE_NONE,
        $this->trans('commands.sg.generate-files.options.public')
      )
      ->addOption(
        'code',
        NULL,
        InputOption::VALUE_NONE,
        $this->trans('commands.sg.generate-files.options.code')
      )
      ->setAliases(['sgf']);
  }

  /**
   * {@inheritdoc}
   * @throws \Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $elapsed_time = 0;
    if (empty($input->getOption('public')) && empty($input->getOption('code'))) {
      $elapsed_time = $this->staticGenerator->generateFiles();
    }
    else {
      if ($input->getOption('public')) {
        $elapsed_time = $this->staticGenerator->generatePublicFiles();
      }
      if ($input->getOption('code')) {
        $elapsed_time = $this->staticGenerator->generateCodeFiles();
      }
    }

    //$this->getIo()->info($this->trans('commands.sg.generate-files.messages.success'));
    $this->getIo()->info('Files generation complete, elapsed time: ' . $elapsed_time . ' seconds.');

  }

}
