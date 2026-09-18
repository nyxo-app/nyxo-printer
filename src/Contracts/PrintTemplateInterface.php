<?php

declare(strict_types=1);

namespace Nyxo\Printer\Contracts;

use Nyxo\Printer\Builders\ThermalBuilder;

interface PrintTemplateInterface
{
    /**
     * Construye el documento térmico encadenando métodos sobre el Builder.
     */
    public function build(ThermalBuilder $ticket): void;
}
