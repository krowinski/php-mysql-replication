<?php

declare(strict_types=1);

namespace MySQLReplication\Event\RowEvent;

use Doctrine\Common\Collections\ArrayCollection;
use JsonSerializable;

/**
 * @extends ArrayCollection<int, ColumnDTO>
 */
class ColumnDTOCollection extends ArrayCollection implements JsonSerializable
{
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
