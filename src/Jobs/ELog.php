<?php

namespace Duduke\Elog\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class ELog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $backoff = 5;
    public $tries = 2;

    protected $index;
    protected $context;
    protected $channel;
    protected $msg;


    /**
     * Create a new job instance.
     *
     * @param string $index
     * @param array $context
     * @param string $msg
     */
    public function __construct(string $index, array $context, string $msg = '')
    {
        if (blank($context)) {
            return;
        }

        $base = [
            'id' => request()->server('X-LOG-ID', ''),
            'method' => request()->getMethod(),
            'uri' => request()->getPathInfo(),
            'ip' => request()->getClientIp(),
            'index' => $index,
        ];

        $this->index = $index;
        $this->context = array_merge($base, $context);
        $this->channel = config('logging.default');
        $this->msg = $msg;

        $writeFile = config('elog.tofile', true);
        $writeEs = config('elog.toes', true);

        if ($writeFile && $writeEs) {
            $this->channel = $this->checkChannel('e_' . $index);
        } else {
            if ($writeFile) {
                $this->channel = $this->checkChannel($index);
            }
            if ($writeEs) {
                $this->channel = $this->checkChannel('es');
            }
        }

    }

    private function checkChannel(string $c): string
    {
        $channel = config('logging.channels');

        if (!array_key_exists($c, $channel)) {
            throw new \Exception($c . ' logger channel is not support');
        }

        return $c;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            \Illuminate\Support\Facades\Log::channel($this->channel)->debug($this->msg, $this->context);
        } catch (\RuntimeException $exception) {
            \Illuminate\Support\Facades\Log::channel($this->channel)->error($exception->getMessage(), [$exception]);
        }
    }
}
