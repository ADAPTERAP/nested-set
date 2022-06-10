WITH items AS (/* filter */),
     deletingItemIds AS (
         SELECT t.$idName, t.$lftName, t.$rgtName
         FROM $tableName AS t
                  JOIN items i ON t.$lftName >= i.$lftName AND t.$rgtName <= i.$rgtName
        $whereScopes
     ),
     deletedAts ($idName, $deletedAtName) AS (
         SELECT $idName, CURRENT_TIMESTAMP FROM items
     ),
     deletedAtsForDeletingItems ($idName, $deletedAtName) AS (
         SELECT dii.$idName, COALESCE(da.$deletedAtName, CURRENT_TIMESTAMP)
         FROM deletingItemIds AS dii
                  LEFT JOIN items i ON dii.$lftName > i.$lftName AND dii.$rgtName < i.$rgtName
                  LEFT JOIN deletedAts da ON da.$idName = i.$idName OR dii.$idName = da.$idName
     )
UPDATE $tableName AS t
SET $lftName= CASE
               WHEN $deletedAtName IS NULL
                   AND NOT EXISTS(SELECT 1 FROM deletingItemIds AS dii WHERE dii.$idName = t.$idName)
                   AND EXISTS(SELECT 1 FROM items AS i WHERE t.$lftName > i.$lftName)
                   THEN $lftName -
                        (SELECT SUM(i.$rgtName - i.$lftName + 1) FROM items AS i WHERE i.$lftName < t.$lftName)
               ELSE $lftName
           END,
    $rgtName = CASE
               WHEN $deletedAtName IS NULL
                   AND NOT EXISTS(SELECT 1 FROM deletingItemIds AS dii WHERE dii.$idName = t.$idName)
                   AND EXISTS(SELECT 1 FROM items AS i WHERE t.$rgtName > i.$rgtName)
                   THEN $rgtName -
                        (SELECT SUM(i.$rgtName - i.$lftName + 1) FROM items AS i WHERE i.$rgtName < t.$rgtName)
               ELSE $rgtName
            END,
    $deletedAtName = CASE
                       WHEN EXISTS(SELECT 1 FROM deletedAtsForDeletingItems AS dafdi WHERE dafdi.$idName = t.$idName)
                           THEN (SELECT $deletedAtName
                                 FROM deletedAtsForDeletingItems AS dafdi
                                 WHERE dafdi.$idName = t.$idName)
                       ELSE $deletedAtName
                   END
$whereScopes
