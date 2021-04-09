<?php
declare(strict_types=1);

namespace Minimal\Database\Statement;

use PDOStatement as PHPPDOStatement;
use Minimal\Database\Contracts\StatementInterface;

/**
 * PDO语句类
 */
class PDOStatement extends PHPPDOStatement implements StatementInterface
{
}