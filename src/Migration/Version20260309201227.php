<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260309201227 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the PHP_SERIALIZED_TO_JSON routine';
    }

    public function up(Schema $schema): void
    {
        $routine = <<<SQL
CREATE FUNCTION PHP_SERIALIZED_TO_JSON(serialized TEXT)
RETURNS TEXT
DETERMINISTIC
BEGIN
  DECLARE result     TEXT    DEFAULT '{';
  DECLARE pos        INT     DEFAULT 1;
  DECLARE type_char  CHAR(1);
  DECLARE str_len    INT;
  DECLARE str_val    TEXT;
  DECLARE num_str    TEXT;
  DECLARE count_str  TEXT;
  DECLARE elem_count INT;
  DECLARE i          INT     DEFAULT 0;

  IF serialized IS NULL OR LEFT(serialized, 2) != 'a:' THEN
      RETURN NULL;
  END IF;

  -- Extract element count from "a:N:{"
  SET pos        = 3;
  SET count_str  = SUBSTRING_INDEX(SUBSTRING(serialized, pos), ':{', 1);
  SET elem_count = CAST(count_str AS UNSIGNED);
  SET pos        = pos + LENGTH(count_str) + 2; -- skip "N:{"

  -- Parse elem_count key/value pairs (2 tokens each)
  WHILE i < elem_count * 2 DO
      SET type_char = SUBSTRING(serialized, pos, 1);
      SET pos       = pos + 2; -- skip "X:" (for N; this also consumes the ";")

      CASE type_char

          WHEN 's' THEN
              -- s:LEN:"VALUE";
              SET num_str = SUBSTRING_INDEX(SUBSTRING(serialized, pos), ':"', 1);
              SET str_len = CAST(num_str AS UNSIGNED);
              SET pos     = pos + LENGTH(num_str) + 2; -- skip 'LEN:"'
              SET str_val = SUBSTRING(serialized, pos, str_len);
              SET pos     = pos + str_len + 2;          -- skip value + '";'

              IF MOD(i, 2) = 0 THEN
                  IF i > 0 THEN SET result = CONCAT(result, ','); END IF;
                  SET result = CONCAT(result, JSON_QUOTE(str_val), ':');
              ELSE
                  SET result = CONCAT(result, JSON_QUOTE(str_val));
              END IF;

          WHEN 'i' THEN
              -- i:VALUE;
              SET num_str = SUBSTRING_INDEX(SUBSTRING(serialized, pos), ';', 1);
              SET pos     = pos + LENGTH(num_str) + 1;

              IF MOD(i, 2) = 0 THEN
                  IF i > 0 THEN SET result = CONCAT(result, ','); END IF;
                  SET result = CONCAT(result, JSON_QUOTE(num_str), ':');
              ELSE
                  SET result = CONCAT(result, num_str);
              END IF;

          WHEN 'd' THEN
              -- d:VALUE;
              SET num_str = SUBSTRING_INDEX(SUBSTRING(serialized, pos), ';', 1);
              SET pos     = pos + LENGTH(num_str) + 1;

              IF MOD(i, 2) = 0 THEN
                  IF i > 0 THEN SET result = CONCAT(result, ','); END IF;
                  SET result = CONCAT(result, JSON_QUOTE(num_str), ':');
              ELSE
                  SET result = CONCAT(result, num_str);
              END IF;

          WHEN 'b' THEN
              -- b:0; or b:1;
              SET num_str = SUBSTRING_INDEX(SUBSTRING(serialized, pos), ';', 1);
              SET pos     = pos + LENGTH(num_str) + 1;

              IF MOD(i, 2) = 0 THEN
                  IF i > 0 THEN SET result = CONCAT(result, ','); END IF;
                  SET result = CONCAT(result, JSON_QUOTE(IF(num_str = '1', 'true', 'false')), ':');
              ELSE
                  SET result = CONCAT(result, IF(num_str = '1', 'true', 'false'));
              END IF;

          WHEN 'N' THEN
              -- N; (null) — pos+2 already consumed 'N' and ';'
              IF MOD(i, 2) = 0 THEN
                  IF i > 0 THEN SET result = CONCAT(result, ','); END IF;
                  SET result = CONCAT(result, '"null":');
              ELSE
                  SET result = CONCAT(result, 'null');
              END IF;

      END CASE;

      SET i = i + 1;
  END WHILE;

  RETURN CONCAT(result, '}');
END
SQL;
        $this->addSql($routine);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP FUNCTION IF EXISTS PHP_SERIALIZED_TO_JSON");
    }
}
