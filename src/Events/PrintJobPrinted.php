<?php

declare(strict_types=1);

namespace Nyxo\Printer\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Nyxo\Printer\Models\PrintJob;

class PrintJobPrinted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PrintJob $printJob
    ) {}
}
