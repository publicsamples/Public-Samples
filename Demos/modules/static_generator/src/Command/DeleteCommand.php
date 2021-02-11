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
 * Class DeleteAllCommand.
 *
 * @DrupalCommand (
 *     extension="static_generator",
 *     extensionType="module"
 * )
 */
class DeleteCommand extends ContainerAwareCommand {

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
      ->setName('sg:delete')
      ->setDescription($this->trans('commands.sg.delete-all.description'))
      ->addOption(
        'pages',
        NULL,
        InputOption::VALUE_NONE,
        $this->trans('commands.sg.delete.options.pages')
      )
      ->addOption(
        'esi',
        NULL,
        InputOption::VALUE_NONE,
        $this->trans('commands.sg.delete.options.esi')
      )
      ->setAliases(['sgd']);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    if ($input->getOption('pages')) {
      $answer = $this->getIo()->ask('Delete all pages (yes/no)? ');
      if (strtolower($answer) == 'yes') {
        $elapsed_time = $this->staticGenerator->deletePages();
        $this->getIo()
          ->info('Delete all generated pages completed, elapsed time: ' . $elapsed_time . ' seconds.');
      }
    }
    elseif ($input->getOption('esi')) {
      $answer = $this->getIo()->ask('Delete all ESIs (yes/no)?');
      if (strtolower($answer) == 'yes') {
        $elapsed_time = $this->staticGenerator->deleteEsi();
        $this->getIo()
          ->info('Delete all generated ESIs completed, elapsed time: ' . $elapsed_time . ' seconds.');
      }
    }
    else {
      $answer = $this->getIo()->ask('Delete all ESIs, files and pages (yes/no)?');
      if (strtolower($answer) == 'yes') {
        $elapsed_time = $this->staticGenerator->deleteAll();
        $this->getIo()
          ->info('Delete all generated ESIs, files and pages completed, elapsed time: ' . $elapsed_time . ' seconds.');
      }
    }
  }
}
