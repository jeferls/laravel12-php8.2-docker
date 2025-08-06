<?php

namespace App\Console\Commands;

use Domain\Shared\Helpers\Queue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpAmqpLib\Message\AMQPMessage;

class Work extends Command
{
    protected $signature = 'work {queue} {--prefetch=} {--assure=false} {--onerror=requeue} {--delay=0} {--maxWorkCount=0} {--requeueMode=} {--requeueTime=5} {--processesEachItem=} {--processEveryTime=5}';

    protected $description = 'Comando apenas para debug';

    private function getFib($n)
    {
        return round(pow((sqrt(5) + 1) / 2, $n) / sqrt(5));
    }

    private $workCount = 0;

    private $processesEachItemCount = 0;

    public function handle()
    {
        $prefetch = (int) $this->option('prefetch');
        $assure = ($this->option('assure') === 'true');
        $onerror = $this->option('onerror');
        $queue = $this->argument('queue');
        $delay = $this->option('delay') ?? 0;
        $requeueMode = $this->option('requeueMode') ?? 'nack';
        $maxWorkCount = (int) $this->option('maxWorkCount') ?? 0;
        $requeueTime = (int) $this->option('requeueTime') ?? 60;

        $processesEachItem = $this->option('processesEachItem') ?? 0;
        $processEveryTime = $this->option('processEveryTime') ?? 0;

        if ($prefetch) {
            Queue::setQos(0, $prefetch, false);
        }

        $workerID = Str::uuid();

        $consumerCount = Queue::getConsumerCount($queue);

        if ($delay > 0 && $consumerCount > 0) {
            $delay = $delay + $this->getFib($consumerCount + 2);
        }

        Queue::consume($queue, function (AMQPMessage $message) use ($queue, $assure, $onerror, $delay, $maxWorkCount, $requeueMode, $workerID, $requeueTime, $processesEachItem, $processEveryTime) {
            if ($maxWorkCount) {
                $this->workCount++;
                if ($this->workCount > $maxWorkCount) {
                    Log::debug('Worker -> killing worker', [
                        'workerPID' => getmypid(),
                        'workCount' => $this->workCount,
                        'maxWorkCount' => $maxWorkCount,
                    ]);
                    exit(0);
                }
            }
            try {
                $res = Queue::processMessage($message, $delay, $workerID);
                if ($delay && is_string($res) && $res = '__delay__') {
                    sleep($requeueTime);
                    if ($requeueMode === 'requeue') {
                        Queue::directPublish($queue, $message);
                        $message->ack();
                    } else {
                        $message->nack(true);
                    }
                } elseif ($assure) {
                    if ($res) {
                        $message->ack();
                    } else {
                        Log::error('Worker -> assure -> requeuing', [
                            'queue' => $message->getRoutingKey(),
                            'message' => $message->getBody(),
                            'res' => $res,
                        ]);
                        sleep($requeueTime);
                        if ($requeueMode === 'requeue') {
                            Queue::directPublish($queue, $message);
                            $message->ack();
                        } else {
                            $message->nack(true);
                        }
                    }
                } elseif ($processesEachItem) {

                    $this->processesEachItemCount++;

                    Log::debug('PROCESSANDO POR PACOTE DE ITEM', [
                        'requeueMode' => $requeueMode,
                        'processesEachItemCount' => $this->processesEachItemCount ,
                        'processesEachItem' => (int)$processesEachItem
                    ]);

                    if ($this->processesEachItemCount === (int) $processesEachItem) {
                        $this->processesEachItemCount = 0;
                        Log::debug('PROCESSANDO SLEEP');
                        sleep((int)$processEveryTime);
                    }

                    $message->ack();

                } else {
                    $message->ack();
                }

                Queue::commit();
            } catch (\Throwable $th) {
                if ($onerror === 'requeue') {
                    Log::error('Worker -> error -> requeuing', [
                        'queue' => $message->getRoutingKey(),
                        // 'message' => $message->getBody(),
                        'errCode' => $th->getCode(),
                        'errMessage' => $th->getMessage(),
                        'trace' => $th->getTrace(),
                    ]);
                    sleep($requeueTime);
                    if ($requeueMode === 'requeue') {
                        Queue::directPublish($queue, $message);
                        $message->ack();
                    } else {
                        $message->nack(true);
                    }
                } else {
                    Log::error('Worker -> error -> not requeuing', [
                        'queue' => $message->getRoutingKey(),
                        'message' => $message->getBody(),
                        'errCode' => $th->getCode(),
                        'errMessage' => $th->getMessage(),
                        'trace' => $th->getTrace(),
                    ]);
                    $message->ack();
                }
                Queue::commit();
            }
        });
    }
}
