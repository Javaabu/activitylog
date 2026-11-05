<?php

namespace Javaabu\Activitylog\Rollback;

use RuntimeException;

/**
 * A log that can't be rolled back to; the message says why
 */
class RollbackNotPossible extends RuntimeException {}
