<?php

declare(strict_types=1);

namespace MySQLGridTests;

use MySQLGrid;
use PDO;

final class MySQLGridSqliteAdapter extends MySQLGrid {
    private ?PDO $sqlite = null;

    public function __construct(?PDO $sqlite = null) {
        parent::__construct();
        $this->sqlite = $sqlite;
    }

    public function setSqliteConnection(PDO $sqlite): void {
        $this->sqlite = $sqlite;
    }

    public function connect(): void {
        if ($this->sqlite === null) {
            $this->sqlite = SqliteTestDatabase::createConnection();
        }
        $this->db = $this->sqlite;
    }

    public function disconnect(): void {
        $this->db = null;
        $this->sqlite = null;
    }

    public function useAllColumns(): void {
        $this->assertConnected();

        $statement = $this->sqlite->query("PRAGMA table_info(" . $this->table . ")");
        $rows = $statement !== false ? $statement->fetchAll() : array();

        $this->columns = array();
        foreach ($rows as $row) {
            $this->columns[] = array("field" => (string)$row["name"]);
        }
    }

    public function addData(mixed $data): void {
        if (!$this->can_add) {
            return;
        }

        $this->assertConnected();

        $hook = $this->add_before;
        if (is_callable($hook) && !$hook($this, $data)) {
            return;
        }

        $columns = array();
        $params = array();

        for ($i = 0; $i < count($this->columns); $i++) {
            $columnName = (string)$this->columns[$i]["field"];
            $columnType = $this->columns[$i]["type"] ?? PHPMYSQLGRID_TEXT;
            $value = $data[$i] ?? null;

            if ($columnType === PHPMYSQLGRID_PASSWORD && $value === PHPMYSQLGRID_PWDUMMY) {
                continue;
            }

            if (isset($this->columns[$i]["convert_input"])) {
                $value = $this->columns[$i]["convert_input"](
                    $this,
                    $value,
                    $i + $this->countPrimaries(),
                    array_merge((array)false, (array)$data)
                );
            }

            $columns[] = $columnName;
            $params[":" . $columnName] = $value;
        }

        foreach ($this->add_values as $key => $value) {
            $columnName = (string)$key;
            $columns[] = $columnName;
            $params[":" . $columnName] = $value;
        }

        if ($columns === array()) {
            return;
        }

        $placeholders = array_map(static fn(string $column): string => ":" . $column, $columns);
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            implode(",", $columns),
            implode(",", $placeholders)
        );

        $statement = $this->sqlite->prepare($sql);
        $statement->execute($params);

        $after = $this->add_after;
        if (is_callable($after)) {
            $after($this, (int)$this->sqlite->lastInsertId(), $data);
        }
    }

    public function editData(mixed $id, mixed $data): void {
        if (!$this->can_edit) {
            return;
        }

        $this->assertConnected();

        $hook = $this->edit_before;
        if (is_callable($hook) && !$hook($this, $id, $data)) {
            return;
        }

        $updates = array();
        $params = array();

        for ($i = 0; $i < count($this->columns); $i++) {
            $columnName = (string)$this->columns[$i]["field"];
            $columnType = $this->columns[$i]["type"] ?? PHPMYSQLGRID_TEXT;
            $value = $data[$i] ?? null;

            if ($columnType === PHPMYSQLGRID_PASSWORD && $value === PHPMYSQLGRID_PWDUMMY) {
                continue;
            }

            if (isset($this->columns[$i]["convert_input"])) {
                $value = $this->columns[$i]["convert_input"](
                    $this,
                    $value,
                    $i + $this->countPrimaries(),
                    array_merge((array)$id, (array)$data)
                );
            }

            $updates[] = $columnName . " = :" . $columnName;
            $params[":" . $columnName] = $value;
        }

        if ($updates === array()) {
            return;
        }

        $params[":primary_id"] = $id;

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s = :primary_id",
            $this->table,
            implode(",", $updates),
            $this->primary
        );

        $statement = $this->sqlite->prepare($sql);
        $statement->execute($params);

        $after = $this->edit_after;
        if (is_callable($after)) {
            $after($this, $id, $data);
        }
    }

    public function deleteData(mixed $id): void {
        if (!$this->can_delete) {
            return;
        }

        $this->assertConnected();

        $hook = $this->delete_before;
        if (is_callable($hook) && !$hook($this, $id)) {
            return;
        }

        $sql = sprintf("DELETE FROM %s WHERE %s = :primary_id", $this->table, $this->primary);
        $statement = $this->sqlite->prepare($sql);
        $statement->execute(array(":primary_id" => $id));

        $after = $this->delete_after;
        if (is_callable($after)) {
            $after($this, $id);
        }
    }

    public function fetchRows(int $limit = 50, int $offset = 0): array {
        $this->assertConnected();

        $sortColumn = $this->columns[$this->sort]["field"] ?? $this->primary;
        $direction = $this->dir ? "DESC" : "ASC";

        $sql = sprintf(
            "SELECT * FROM %s ORDER BY %s %s LIMIT :limit OFFSET :offset",
            $this->table,
            $sortColumn,
            $direction
        );

        $statement = $this->sqlite->prepare($sql);
        $statement->bindValue(":limit", $limit, PDO::PARAM_INT);
        $statement->bindValue(":offset", $offset, PDO::PARAM_INT);
        $statement->execute();

        $rows = $statement->fetchAll();

        return is_array($rows) ? $rows : array();
    }

    public function tableRowCount(): int {
        $this->assertConnected();

        $statement = $this->sqlite->query("SELECT COUNT(*) FROM " . $this->table);

        return $statement !== false ? (int)$statement->fetchColumn() : 0;
    }

    private function assertConnected(): void {
        if (!$this->sqlite instanceof PDO) {
            throw new \RuntimeException("SQLite connection is not initialized. Call connect() first.");
        }
    }
}
