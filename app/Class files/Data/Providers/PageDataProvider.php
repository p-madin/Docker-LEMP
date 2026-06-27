<?php
class PageDataProvider implements DataProviderInterface {
    public function getColumns(): array {
        return [
            ['key' => 'pagPK', 'label' => 'ID'],
            ['key' => 'pagTitle', 'label' => 'Title'],
            ['key' => 'nbPath', 'label' => 'Nav Bar Path(s)'],
            ['key' => 'pagCreated', 'label' => 'Created'],
            ['key' => 'actions', 'label' => 'Actions', 'action' => 'multi', 'actions' => [
                ['type' => 'button_form', 'config' => ['url' => '/page_editor?id=', 'param' => 'pagPK', 'buttonLabel' => 'Edit']],
                ['type' => 'button_form', 'config' => ['url' => '/preview?id=', 'param' => 'pagPK', 'buttonLabel' => 'View']],
                ['type' => 'button_form', 'config' => ['url' => '/pageAction', 'buttonLabel' => 'Delete', 'params' => ['action' => 'delete', 'pagPK' => 'pagPK'], 'cssClasses' => ['delete']]]
            ]]
        ];
    }

    public function getData(): array {
        global $db;
        $sql = "SELECT p.pagPK, p.pagTitle, p.pagCreated, n.nbPath
                FROM tblPages p
                LEFT JOIN tblNavBar n ON n.nbPageFK = p.pagPK
                WHERE p.pagDeleted IS NULL 
                ORDER BY p.pagPK ASC, n.nbPK ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Unnormalize: concatenate multiple nbPaths for each page
        $uniquePages = [];
        foreach ($rows as $row) {
            $pk = $row['pagPK'];
            if (!isset($uniquePages[$pk])) {
                $uniquePages[$pk] = $row;
            } else {
                if (!empty($row['nbPath'])) {
                    if (!empty($uniquePages[$pk]['nbPath'])) {
                        $uniquePages[$pk]['nbPath'] .= ', ' . $row['nbPath'];
                    } else {
                        $uniquePages[$pk]['nbPath'] = $row['nbPath'];
                    }
                }
            }
        }

        return array_values($uniquePages);
    }

    public function getNestedKey(): ?string {
        return null;
    }

    public function getDataSourceName(): string {
        return "Content Pages";
    }
}
