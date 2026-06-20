<?php

namespace Klickmanufaktur\StatamicUmamiAnalytics\Exceptions;

use RuntimeException;

class UmamiConfigurationException extends RuntimeException
{
    /**
     * @param  list<string>  $missing
     */
    public static function missing(array $missing): self
    {
        return new self('Umami ist nicht vollstaendig konfiguriert: '.implode(', ', $missing).'.');
    }
}
