<?php
// phpcs:ignoreFile
namespace Drupal\package_manager_testing;

use Drupal\Core\Controller\ControllerBase;
use Drupal\package_manager\PathLocator;
use Drupal\package_manager\Stage;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InstallController extends ControllerBase {

  /**
   * The stage.
   *
   * @var \Drupal\package_manager\Stage
   */
  protected $stage;

  /**
   * The path locator service.
   *
   * @var \Drupal\package_manager\PathLocator
   */
  private $pathLocator;
  /**
   * Constructs an ApiController object.
   *
   * @param \Drupal\package_manager\Stage $stage
   *   The stage.
   * @param \Drupal\package_manager\PathLocator $path_locator
   *   The path locator service.
   */
  public function __construct(Stage $stage, PathLocator $path_locator) {
    $this->stage = $stage;
    $this->pathLocator = $path_locator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $stage = new Stage(
      $container->get('config.factory'),
      $container->get('package_manager.path_locator'),
      $container->get('package_manager.beginner'),
      $container->get('package_manager.stager'),
      $container->get('package_manager.committer'),
      $container->get('file_system'),
      $container->get('event_dispatcher'),
      $container->get('tempstore.shared'),
      $container->get('datetime.time')
    );
    return new static(
      $stage,
      $container->get('package_manager.path_locator')
    );
  }
  public function install() {
    set_time_limit(0);
    $active = $this->stage->getActiveComposer();
    $installed = $active->getInstalledPackages();
    if (empty($installed['drupal/admin_toolbar'])) {
      throw new \LogicException("you have to have drupal/admin_toolbar installed to test");
    }
    if (!empty($installed['drupal/admin_toolbar_content'])) {
      throw new \LogicException("you cannot have drupal/admin_toolbar_content installed to test");
    }
    \Drupal::messenger()->addWarning('starting');


    if ($this->stage->isAvailable()) {
      $this->stage->create();
      $this->stage->require(['drupal/admin_toolbar_content']);
      $this->stage->apply();
      $this->stage->postApply();
      \Drupal::messenger()->addError('was able to apply without error. stage not destroyed to inspect: ' . $this->stage->getStageDirectory());
    }
    else {
      $this->stage->destroy(true);
      \Drupal::messenger()->addWarning('destroyed stage try again');
    }
    return ['test' =>
      [
        '#type' => 'markup',
        '#markup' => 'done',
      ]
    ];

  }
}
