<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Schema\v2_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\NoteBundle\Migration\UpdateNoteAssociationKindForRenamedEntitiesMigration;

class MigrateNotesRelations extends UpdateNoteAssociationKindForRenamedEntitiesMigration
{
    protected $entitiesNames = [
        'AddressBook',
        'AddressBookContact',
        'AddressBookContactsExport',
        'Campaign',
        'Contact',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getRenamedEntitiesNames(Schema $schema)
    {
        $oldNameSpace = 'OroCRM\Bundle\DotmailerBundle\Entity';
        $newNameSpace = 'Oro\Bundle\DotmailerBundle\Entity';

        $renamedEntityNamesMapping = [];
        foreach ($this->entitiesNames as $entityName) {
            $renamedEntityNamesMapping["$newNameSpace\\$entityName"] = "$oldNameSpace\\$entityName";
        }

        return $renamedEntityNamesMapping;
    }
}
