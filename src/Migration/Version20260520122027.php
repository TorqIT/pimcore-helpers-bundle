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
            create procedure DELETE_DATA_OBJECTS (in className varchar(190), in ids json)
            begin
                declare exit handler for sqlexception
                begin
                    rollback;
                    drop temporary table if exists _delete_object_ids;
                end;

                start transaction;
                    # ensure class exists
                    if (select count(*) != 1 from classes where name = className) then
                        signal sqlstate '45000' set message_text = 'No entry in classes table for parameter className';
                    end if;

                    create temporary table _delete_object_ids (id int not null, primary key (id));

                    if ids is null then
                        insert into _delete_object_ids (id) select id from objects where objects.className = className;
                    else
                        insert into _delete_object_ids (id)
                            select jt.value from json_table(ids, '$[*]' columns (value int path '$')) jt
                            join objects o on o.id = jt.value and o.className = className;
                    end if;

                    # dependencies
                    delete t1 from dependencies t1 join _delete_object_ids t on t1.sourceid = t.id and t1.sourcetype = 'object';
                    delete t1 from dependencies t1 join _delete_object_ids t on t1.targetid = t.id and t1.targettype = 'object';

                    # properties
                    delete t1 from properties t1 join _delete_object_ids t on t1.cid = t.id and t1.ctype = 'object';

                    # schedule_tasks
                    delete t1 from schedule_tasks t1 join _delete_object_ids t on t1.cid = t.id and t1.ctype = 'object';

                    # notes
                    delete t1 from notes t1 join _delete_object_ids t on t1.cid = t.id and t1.ctype = 'object';

                    # versions
                    delete t1 from versions t1 join _delete_object_ids t on t1.cid = t.id and t1.ctype = 'object';

                    # tags_assignment
                    delete t1 from tags_assignment t1 join _delete_object_ids t on t1.cid = t.id and t1.ctype = 'object';

                    # objects
                    delete o from objects o join _delete_object_ids t on o.id = t.id;

                    drop temporary table _delete_object_ids;
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
