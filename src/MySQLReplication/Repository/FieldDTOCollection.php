<?php
declare(strict_types=1);

namespace MySQLReplication\Repository;

use Doctrine\Common\Collections\ArrayCollection;

class FieldDTOCollection extends ArrayCollection
{
    public static function makeFromArray(array $fields): self
    {
        $collection = new self();
        foreach ($fields as $field) {
            $collection->add(FieldDTO::makeFromArray($field));
        }

        return $collection;
    }
}