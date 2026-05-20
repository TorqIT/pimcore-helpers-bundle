<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260520122027 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the DELETE_DATA_OBJECTS procedure.';
    }

    public function up(Schema $schema): void
    {
        $procedure = <<<SQL
            create procedure DELETE_DATA_OBJECTS (in className varchar(190))
            begin
                declare exit handler for sqlexception 
                begin
                    rollback;
                end;

                start transaction;
                    # ensure class exists
                    if (select count(*) != 1 from classes where name = className) then
                        signal sqlstate '45000' set message_text = concat('No entry in classes table with className: ', className);
                    end if;

                    # dependencies
                    delete t1 from dependencies t1 join objects o on t1.sourceid = o.id and t1.sourcetype = 'object' where o.className = className;
                    delete t1 from dependencies t1 join objects o on t1.targetid = o.id and t1.targettype = 'object' where o.className = className;

                    # properties
                    delete t1 from properties t1 join objects o on t1.cid = o.id and t1.ctype = 'object' where o.className = className;

                    # schedule_tasks
                    delete t1 from schedule_tasks t1 join objects o on t1.cid = o.id and t1.ctype = 'object' where o.className = className;

                    # notes
                    delete t1 from notes t1 join objects o on t1.cid = o.id and t1.ctype = 'object' where o.className = className;

                    # versions
                    delete t1 from versions t1 join objects o on t1.cid = o.id and t1.ctype = 'object' where o.className = className;

                    # tags_assignment
                    delete t1 from tags_assignment t1 join objects o on t1.cid = o.id and t1.ctype = 'object' where o.className = className;

                    # objects
                    delete from objects where objects.className = className;
                commit;
            end;
        SQL;
        $this->addSql($procedure);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            drop procedure DELETE_DATA_OBJECTS
        SQL);
    }
}
