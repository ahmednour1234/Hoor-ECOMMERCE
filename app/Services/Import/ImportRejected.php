<?php

declare(strict_types=1);

namespace App\Services\Import;

use RuntimeException;

/**
 * Thrown to roll the import back when a row is rejected mid-write.

 * Carries no message: the reason is already recorded against its row in the
 * result. This exists only to unwind the transaction, so an import that
 * refuses row 40 does not leave rows 1 to 39 committed.
 */
class ImportRejected extends RuntimeException
{
}
