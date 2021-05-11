WITH `items` AS (/* filter */),
     `deletingItemIds` AS (
         SELECT `t`.`id`, `t`.`lft`, `t`.`rgt`
         FROM `table` `t`
                  JOIN `items` `i` ON `t`.`lft` >= `i`.`lft` AND `t`.`rgt` <= `i`.`rgt`
     ),
     `deletedAts` (`id`, `deleted_at`) AS (
         SELECT `id`, NOW() FROM items
     ),
     `deletedAtsForDeletingItems` (`id`, `deleted_at`) AS (
         SELECT `dii`.`id`, COALESCE(`da`.`deleted_at`, NOW())
         FROM `deletingItemIds` `dii`
                  LEFT JOIN `items` `i` ON `dii`.`lft` > `i`.`lft` AND `dii`.`rgt` < `i`.`rgt`
                  LEFT JOIN `deletedAts` `da` ON `da`.`id` = `i`.`id` OR `dii`.`id` = `da`.`id`
     )
UPDATE `table` `t`
SET `lft`= CASE
               WHEN `deleted_at` IS NULL
                   AND NOT EXISTS(SELECT 1 FROM `deletingItemIds` `dii` WHERE `dii`.`id` = `t`.`id`)
                   AND EXISTS(SELECT 1 FROM `items` `i` WHERE `t`.`lft` > i.`lft`)
                   THEN `lft` -
                        (SELECT SUM(`i`.`rgt` - `i`.`lft` + 1) FROM `items` `i` WHERE `i`.`lft` < `t`.`lft`)
               ELSE `lft`
           END,
    `rgt` = CASE
               WHEN `deleted_at` IS NULL
                   AND NOT EXISTS(SELECT 1 FROM `deletingItemIds` `dii` WHERE `dii`.`id` = `t`.`id`)
                   AND EXISTS(SELECT 1 FROM `items` `i` WHERE `t`.`rgt` > `i`.`rgt`)
                   THEN `rgt` -
                        (SELECT SUM(`i`.`rgt` - `i`.`lft` + 1) FROM `items` `i` WHERE `i`.`rgt` < `t`.`rgt`)
               ELSE `rgt`
            END,
    `deleted_at` = CASE
                       WHEN EXISTS(SELECT 1 FROM `deletedAtsForDeletingItems` `dafdi` WHERE `dafdi`.`id` = `t`.`id`)
                           THEN (SELECT `deleted_at`
                                 FROM `deletedAtsForDeletingItems` `dafdi`
                                 WHERE `dafdi`.`id` = `t`.`id`)
                       ELSE `deleted_at`
                   END
