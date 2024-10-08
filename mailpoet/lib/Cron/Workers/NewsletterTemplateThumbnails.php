<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\NewsletterTemplates\ThumbnailSaver;

class NewsletterTemplateThumbnails extends SimpleWorker {
  const TASK_TYPE = 'newsletter_templates_thumbnails';
  const AUTOMATIC_SCHEDULING = false;
  const SUPPORT_MULTIPLE_INSTANCES = false;

  /** @var ThumbnailSaver */
  private $thumbnailSaver;

  public function __construct(
    ThumbnailSaver $thumbnailSaver
  ) {
    parent::__construct();
    $this->thumbnailSaver = $thumbnailSaver;
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    $this->thumbnailSaver->ensureTemplateThumbnailsForAll();
    return true;
  }
}
