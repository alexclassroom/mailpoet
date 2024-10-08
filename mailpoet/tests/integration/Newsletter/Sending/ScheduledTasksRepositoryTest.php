<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

use MailPoet\Cron\Workers\Bounce;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\SendingQueue;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Carbon\CarbonImmutable;

class ScheduledTasksRepositoryTest extends \MailPoetTest {
  private ScheduledTasksRepository $repository;
  private ScheduledTaskFactory $scheduledTaskFactory;
  private SendingQueue $sendingQueueFactory;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->scheduledTaskFactory = new ScheduledTaskFactory();
    $this->sendingQueueFactory = new SendingQueue();
  }

  public function testItCanGetDueTasks() {
    $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subDay(), Carbon::now()); // deleted (should not be fetched)
    $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addDay()); // scheduled in future (should not be fetched)
    $this->scheduledTaskFactory->create('test', '', Carbon::now()->subDay()); // wrong status (should not be fetched)
    $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_CANCELLED, Carbon::now()->subDay()); // due, but cancelled (should not be fetched)
    $expectedResult[] = $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subDay()); // due (scheduled in past)
    $expectedResult[] = $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subDay()); // due (scheduled in past)
    $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subDay()); // due (scheduled in past)

    $tasks = $this->repository->findDueByType('test', 2);
    $this->assertCount(2, $tasks);
    $this->assertSame($expectedResult, $tasks);
  }

  public function testItCanGetRunningTasks() {
    $expectedResult[] = $this->scheduledTaskFactory->create('test', null, Carbon::now()->subDay()); // running (scheduled in past)
    $this->scheduledTaskFactory->create('test', null, Carbon::now()->subDay(), Carbon::now()); // deleted (should not be fetched)
    $this->scheduledTaskFactory->create('test', null, Carbon::now()->addDay()); // scheduled in future (should not be fetched)
    $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->subDay()); // wrong status (should not be fetched)
    $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_CANCELLED, Carbon::now()->subDay()); // wrong status (should not be fetched)

    $tasks = $this->repository->findRunningByType('test', 10);
    $this->assertSame($expectedResult, $tasks);
  }

  public function testItCanGetCompletedTasks() {
    $expectedResult[] = $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->subDay()); // completed (scheduled in past)
    $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->subDay(), Carbon::now()); // deleted (should not be fetched)
    $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->addDay()); // scheduled in future (should not be fetched)
    $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subDay()); // wrong status (should not be fetched)
    $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_CANCELLED, Carbon::now()->subDay()); // wrong status (should not be fetched)

    $tasks = $this->repository->findCompletedByType('test', 10);
    $this->assertSame($expectedResult, $tasks);
  }

  public function testItCanGetFutureScheduledTasks() {
    $expectedResult[] = $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addDay()); // scheduled (in future)
    $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addDay(), Carbon::now()); // deleted (should not be fetched)
    $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subDay()); // scheduled in past (should not be fetched)
    $this->scheduledTaskFactory->create('test', null, Carbon::now()->addDay()); // wrong status (should not be fetched)
    $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_CANCELLED, Carbon::now()->addDay()); // wrong status (should not be fetched)

    $tasks = $this->repository->findFutureScheduledByType('test', 10);
    $this->assertSame($expectedResult, $tasks);
  }

  public function testItCanGetRunningSendingTasks(): void {
    // running task
    $task = $this->scheduledTaskFactory->create(SendingQueueWorker::TASK_TYPE, null, Carbon::now()->subDay());
    $this->sendingQueueFactory->create($task);
    $expectedResult[] = $task;
    // deleted task
    $task = $this->scheduledTaskFactory->create(SendingQueueWorker::TASK_TYPE, null, Carbon::now()->subDay(), Carbon::now());
    $this->sendingQueueFactory->create($task);
    // without sending queue
    $this->scheduledTaskFactory->create(SendingQueueWorker::TASK_TYPE, null, Carbon::now()->subDay());
    // scheduled in future
    $task = $this->scheduledTaskFactory->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->addDay());
    $this->sendingQueueFactory->create($task);
    // wrong status
    $task = $this->scheduledTaskFactory->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subDay());
    $this->sendingQueueFactory->create($task);

    $tasks = $this->repository->findRunningSendingTasks();
    $this->assertSame($expectedResult, $tasks);
  }

  public function testCanCountByStatus() {
    $this->scheduledTaskFactory->create('sending', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addDay());
    $this->scheduledTaskFactory->create('sending', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addDays(20));
    $this->scheduledTaskFactory->create('sending', ScheduledTaskEntity::STATUS_PAUSED, Carbon::now()->addDay());
    $this->scheduledTaskFactory->create('sending', ScheduledTaskEntity::STATUS_PAUSED, Carbon::now()->addDays(3));
    $this->scheduledTaskFactory->create('sending', ScheduledTaskEntity::STATUS_PAUSED, Carbon::now()->addDays(5));
    $this->scheduledTaskFactory->create('sending', ScheduledTaskEntity::STATUS_INVALID, Carbon::now()->addDays(4));
    $this->scheduledTaskFactory->create('sending', ScheduledTaskEntity::STATUS_CANCELLED, Carbon::now()->subDay());
    $this->scheduledTaskFactory->create('sending', ScheduledTaskEntity::STATUS_CANCELLED, Carbon::now()->addDay());
    $this->scheduledTaskFactory->create('sending', null, Carbon::now()->addDays(4));

    $counts = $this->repository->getCountsPerStatus();
    $this->assertEquals([
      ScheduledTaskEntity::STATUS_SCHEDULED => 2,
      ScheduledTaskEntity::STATUS_PAUSED => 3,
      ScheduledTaskEntity::STATUS_INVALID => 1,
      ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING => 1,
      ScheduledTaskEntity::STATUS_COMPLETED => 0,
      ScheduledTaskEntity::STATUS_CANCELLED => 2,
    ], $counts);
  }

  public function testItCanFetchBasicTasksData() {
    $task1 = $this->scheduledTaskFactory->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addDay());
    $task2 = $this->scheduledTaskFactory->create(Bounce::TASK_TYPE, ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING, Carbon::now()->addDay());
    $this->scheduledTaskFactory->create(Bounce::TASK_TYPE, ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING, Carbon::now()->addDay(), Carbon::now()); // deleted (should not be fetched)
    $data = $this->repository->getLatestTasks();
    verify(count($data))->equals(2);
    $ids = array_map(function ($d){
      return $d->getId();
    }, $data);
    $types = array_map(function ($d){
      return $d->getType();
    }, $data);
    $this->assertContains($task1->getId(), $ids);
    $this->assertContains($task2->getId(), $ids);
    $this->assertContains(SendingQueueWorker::TASK_TYPE, $types);
    $this->assertContains(Bounce::TASK_TYPE, $types);
    verify(is_int($data[1]->getPriority()))->true();
    verify($data[1]->getUpdatedAt())->instanceOf(\DateTimeInterface::class);
    verify($data[1]->getStatus())->null(); // running tasks have status null
    verify($data[0])->instanceOf(ScheduledTaskEntity::class);
    verify($data[1])->instanceOf(ScheduledTaskEntity::class);
  }

  public function testItCanFilterTasksByType() {
    $this->scheduledTaskFactory->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->addDay());
    $this->scheduledTaskFactory->create(Bounce::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->addDay());
    $this->scheduledTaskFactory->create(Bounce::TASK_TYPE, ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING, Carbon::now()->addDay(), Carbon::now()); // deleted (should not be fetched)
    $data = $this->repository->getLatestTasks(Bounce::TASK_TYPE);
    verify(count($data))->equals(1);
    verify($data[0]->getType())->equals(Bounce::TASK_TYPE);
  }

  public function testItCanFilterTasksByStatus() {
    $this->scheduledTaskFactory->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->addDay());
    $this->scheduledTaskFactory->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_PAUSED, Carbon::now()->addDay());
    $this->scheduledTaskFactory->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_CANCELLED, Carbon::now()->subDay());
    $this->scheduledTaskFactory->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->addDay(), Carbon::now()); // deleted (should not be fetched)
    $data = $this->repository->getLatestTasks(null, [ScheduledTaskEntity::STATUS_COMPLETED]);
    verify(count($data))->equals(1);
    verify($data[0]->getStatus())->equals(ScheduledTaskEntity::STATUS_COMPLETED);
  }

  public function testItDoesNotFailForSendingTaskWithoutQueue() {
    $this->scheduledTaskFactory->create(
      SendingQueueWorker::TASK_TYPE,
      ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING,
      Carbon::now()->addDay()
    );
    $data = $this->repository->getLatestTasks();
    verify(count($data))->equals(1);
  }

  public function testItTouchesAllScheduledTasksByIds(): void {
    $originalUpdatedAt = CarbonImmutable::now()->subDay();
    $touched[] = $this->scheduledTaskFactory->create('test', null, Carbon::now()->subDay(), null, $originalUpdatedAt);
    $untouched[] = $this->scheduledTaskFactory->create('test', null, Carbon::now()->subDay(), null, $originalUpdatedAt);
    $this->repository->touchAllByIds([$touched[0]->getId()]);

    // check touched task
    $this->entityManager->clear();
    $scheduledTask = $this->repository->findOneById($touched[0]->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $updatedAt = $scheduledTask->getUpdatedAt();
    $this->assertInstanceOf(\DateTime::class, $updatedAt);
    $this->assertGreaterThan($originalUpdatedAt->getTimestamp(), $updatedAt->getTimestamp());
    // check untouched task
    $scheduledTask = $this->repository->findOneById($untouched[0]->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $updatedAt = $scheduledTask->getUpdatedAt();
    $this->assertInstanceOf(\DateTime::class, $updatedAt);
    $this->assertEquals($updatedAt->getTimestamp(), $originalUpdatedAt->getTimestamp());
  }

  public function testItCanGetScheduledSendingTasks(): void {
    // scheduled task
    $task = $this->scheduledTaskFactory->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subDay());
    $this->sendingQueueFactory->create($task);
    $expectedResult[] = $task;
    // deleted task
    $task = $this->scheduledTaskFactory->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subDay(), Carbon::now());
    $this->sendingQueueFactory->create($task);
    // without sending queue
    $this->scheduledTaskFactory->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subDay());
    // wrong status
    $task = $this->scheduledTaskFactory->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->subDay());
    $this->sendingQueueFactory->create($task);
    // wrong status
    $task = $this->scheduledTaskFactory->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_CANCELLED, Carbon::now()->subDay());
    $this->sendingQueueFactory->create($task);
    // wrong type
    $task = $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subDay());
    $this->sendingQueueFactory->create($task);
    // scheduled in the future
    $task = $this->scheduledTaskFactory->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addDay());
    $this->sendingQueueFactory->create($task);

    $tasks = $this->repository->findScheduledSendingTasks();
    $this->assertSame($expectedResult, $tasks);
  }

  public function testItCanCancelScheduledOrRunningTask(): void {
    $scheduledTask = $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addDay());
    $this->repository->cancelTask($scheduledTask);
    $cancelledTask = $this->repository->findOneById($scheduledTask->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $cancelledTask);
    $this->assertEquals(ScheduledTaskEntity::STATUS_CANCELLED, $cancelledTask->getStatus());
    $this->assertNotNull($cancelledTask->getCancelledAt());

    $runningTask = $this->scheduledTaskFactory->create('test', null, Carbon::now()->addDay());
    $this->repository->cancelTask($runningTask);
    $cancelledTask = $this->repository->findOneById($runningTask->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $cancelledTask);
    $this->assertEquals(ScheduledTaskEntity::STATUS_CANCELLED, $cancelledTask->getStatus());
    $this->assertNotNull($cancelledTask->getCancelledAt());
  }

  public function testItCantCancelCompletedOrPausedTask(): void {
    $completedTask = $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->addDay());
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Only scheduled and running tasks can be cancelled');
    $this->expectExceptionCode(400);
    $this->repository->cancelTask($completedTask);

    $pausedTask = $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_PAUSED, Carbon::now()->addDay());
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Only scheduled and running tasks can be cancelled');
    $this->expectExceptionCode(400);
    $this->repository->cancelTask($pausedTask);
  }

  public function testItCanRescheduleCancelledScheduledTask(): void {
    $scheduledTask = $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_CANCELLED, Carbon::now()->addDay());
    $this->repository->rescheduleTask($scheduledTask);
    $rescheduledTask = $this->repository->findOneById($scheduledTask->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $rescheduledTask);
    $this->assertEquals(ScheduledTaskEntity::STATUS_SCHEDULED, $rescheduledTask->getStatus());
  }

  public function testItCanRescheduleCancelledRunningTaskAndResumesSendingQueue(): void {
    $sendingQueuesRepositoryMock = $this->createMock(SendingQueuesRepository::class);

    $runningTask = $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_CANCELLED, Carbon::now()->subDay());
    $runningTask->setSendingQueue($this->sendingQueueFactory->create($runningTask));
    $sendingQueuesRepositoryMock->expects($this->once())->method('resume')->with($runningTask->getSendingQueue());

    $scheduledTaskRepositoryMock = new ScheduledTasksRepository($this->entityManager, $sendingQueuesRepositoryMock);
    $scheduledTaskRepositoryMock->rescheduleTask($runningTask);

    $this->assertNull($runningTask->getStatus()); // running task has status null
    $this->assertNull($runningTask->getCancelledAt());
  }

  public function testItCantRescheduleCompletedOrScheduledOrRunningTask(): void {
    $completedTask = $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->addDay());
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Only cancelled tasks can be rescheduled');
    $this->expectExceptionCode(400);
    $this->repository->rescheduleTask($completedTask);

    $scheduledTask = $this->scheduledTaskFactory->create('test', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addDay());
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Only cancelled tasks can be rescheduled');
    $this->expectExceptionCode(400);
    $this->repository->rescheduleTask($scheduledTask);

    $running = $this->scheduledTaskFactory->create('test', null, Carbon::now()->addDay());
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Only cancelled tasks can be rescheduled');
    $this->expectExceptionCode(400);
    $this->repository->rescheduleTask($running);
  }
}
