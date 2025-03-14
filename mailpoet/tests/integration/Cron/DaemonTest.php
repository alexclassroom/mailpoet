<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron;

use Codeception\Stub\Expected;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\CronWorkerRunner;
use MailPoet\Cron\Daemon;
use MailPoet\Cron\Workers\SimpleWorker;
use MailPoet\Cron\Workers\WorkersFactory;
use MailPoet\Entities\LogEntity;
use MailPoet\Logging\LogRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WpFunctions;

class DaemonTest extends \MailPoetTest {
  /** @var SettingsController */
  private $settings;

  /** @var LogRepository */
  private $logRepository;

  /** @var WpFunctions */
  private $wp;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->logRepository = $this->diContainer->get(LogRepository::class);
    $this->wp = $this->diContainer->get(WpFunctions::class);
  }

  public function testItCanRun() {
    $cronWorkerRunner = $this->make(CronWorkerRunner::class, [
      'run' => null,
    ]);
    $data = [
      'token' => 123,
    ];
    $this->settings->set(CronHelper::DAEMON_SETTING, $data);
    $daemon = $this->getServiceWithOverrides(Daemon::class, [
      'cronWorkerRunner' => $cronWorkerRunner,
      'workersFactory' => $this->createWorkersFactoryMock(),
    ]);
    $daemon->run($data);
  }

  public function testItLogErrorFromWorker() {
    $cronWorkerRunner = $this->make(CronWorkerRunner::class, [
      'run' => function () {
        throw new \Exception('Worker error!');
      },
    ]);
    $data = [
      'token' => 123,
    ];
    $this->settings->set(CronHelper::DAEMON_SETTING, $data);
    $daemon = $this->getServiceWithOverrides(Daemon::class, [
      'cronWorkerRunner' => $cronWorkerRunner,
      'workersFactory' => $this->createWorkersFactoryMock(),
    ]);
    $daemon->run($data);
    $log = $this->logRepository->findOneBy(['name' => 'cron', 'level' => 400]);
    $this->assertInstanceOf(LogEntity::class, $log);
    verify($log->getMessage())->stringContainsString('Worker error!');
  }

  public function testItTerminatesWhenExecutionLimitIsReached() {
    // Set execution limit to 0 to get limit exceeded exception
    $limitCallback = function () {
      return 0;
    };
    $this->wp->addFilter('mailpoet_cron_get_execution_limit', $limitCallback);
    $cronWorkerRunner = $this->diContainer->get(CronWorkerRunner::class);
    $data = [
      'token' => 123,
    ];
    $this->settings->set(CronHelper::DAEMON_SETTING, $data);

    // Factory should return only the first worker then we stop because of execution limit
    $factoryMock = $this->make(WorkersFactory::class, [
        'createStatsNotificationsWorker' => $this->createSimpleWorkerMock(),
        'createScheduleWorker' => function () {throw new \Exception('createScheduleWorker should not be called');
        },
    ]);
    $daemon = $this->getServiceWithOverrides(Daemon::class, [
      'cronWorkerRunner' => $cronWorkerRunner,
      'workersFactory' => $factoryMock,
    ]);
    $daemon->run($data);
    $log = $this->logRepository->findOneBy(['name' => 'cron', 'level' => 400]);
    verify($log)->null();
    $this->wp->removeFilter('mailpoet_cron_get_execution_limit', $limitCallback);
  }

  private function createWorkersFactoryMock(array $workers = []) {
    return $this->make(WorkersFactory::class, $workers + [
      'createScheduleWorker' => $this->createSimpleWorkerMock(),
      'createQueueWorker' => $this->createSimpleWorkerMock(),
      'createStatsNotificationsWorker' => $this->createSimpleWorkerMock(),
      'createStatsNotificationsWorkerForAutomatedEmails' => $this->createSimpleWorkerMock(),
      'createSendingServiceKeyCheckWorker' => $this->createSimpleWorkerMock(),
      'createPremiumKeyCheckWorker' => $this->createSimpleWorkerMock(),
      'createSubscribersStatsReportWorker' => $this->createSimpleWorkerMock(),
      'createBounceWorker' => $this->createSimpleWorkerMock(),
      'createWooCommerceSyncWorker' => $this->createSimpleWorkerMock(),
      'createExportFilesCleanupWorker' => $this->createSimpleWorkerMock(),
      'createSubscribersEmailCountsWorker' => $this->createSimpleWorkerMock(),
      'createInactiveSubscribersWorker' => $this->createSimpleWorkerMock(),
      'createAuthorizedSendingEmailsCheckWorker' => $this->createSimpleWorkerMock(),
      'createWooCommercePastOrdersWorker' => $this->createSimpleWorkerMock(),
      'createUnsubscribeTokensWorker' => $this->createSimpleWorkerMock(),
      'createSubscriberLinkTokensWorker' => $this->createSimpleWorkerMock(),
      'createSubscribersEngagementScoreWorker' => $this->createSimpleWorkerMock(),
      'createSubscribersLastEngagementWorker' => $this->createSimpleWorkerMock(),
      'createSubscribersCountCacheRecalculationWorker' => $this->createSimpleWorkerMock(),
      'createReEngagementEmailsSchedulerWorker' => $this->createSimpleWorkerMock(),
      'createNewsletterTemplateThumbnailsWorker' => $this->createSimpleWorkerMock(),
      'createAbandonedCartWorker' => $this->createSimpleWorkerMock(),
      'createBackfillEngagementDataWorker' => $this->createSimpleWorkerMock(),
      'createMixpanelWorker' => $this->createSimpleWorkerMock(),
      ]);
  }

  private function createSimpleWorkerMock() {
    return $this->makeEmpty(SimpleWorker::class, [
      'process' => Expected::once(),
    ]);
  }
}
