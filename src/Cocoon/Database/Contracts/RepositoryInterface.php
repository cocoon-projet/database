<?php
declare(strict_types=1);

namespace Cocoon\Database\Contracts;

/**
 * Interface RepositoryInterface
 * Définit le contrat pour les repositories de base de données
 * @package Cocoon\Database\Contracts
 */
interface RepositoryInterface
{
    /**
     * Récupère tous les enregistrements
     *
     * @param string $columns Colonnes à sélectionner
     * @param int|null $paginate Nombre d'éléments par page
     * @param string $orderByField Champ de tri
     * @param string $order Direction du tri (asc|desc)
     * @return array<mixed>|object
     */
    public function all(
        string $columns = '',
        ?int $paginate = null,
        string $orderByField = 'id',
        string $order = 'desc'
    ): array|object;

    /**
     * Recherche un enregistrement par son ID
     *
     * @param int|string $id Identifiant de l'enregistrement
     * @param string $columns Colonnes à sélectionner
     * @return array<mixed>|object|null
     */
    public function find(int|string $id, string $columns = ''): array|object|null;

    /**
     * Crée un nouvel enregistrement
     *
     * @param array<string,mixed> $data Données à enregistrer
     * @return array<mixed>|object
     */
    public function save(array $data = []): array|object;

    /**
     * Met à jour un enregistrement existant
     *
     * @param int|string $id Identifiant de l'enregistrement
     * @param array<string,mixed> $data Données à mettre à jour
     * @return array<mixed>|object
     */
    public function update(int|string $id, array $data = []): array|object;

    /**
     * Supprime un enregistrement
     *
     * @param int|string $id Identifiant de l'enregistrement
     * @return bool
     */
    public function delete(int|string $id): bool;

    /**
     * Recherche un enregistrement par un champ spécifique
     *
     * @param string $field Champ de recherche
     * @param mixed $value Valeur recherchée
     * @param string $columns Colonnes à sélectionner
     * @return array<mixed>|object|null
     */
    public function findBy(string $field, mixed $value, string $columns = ''): array|object|null;

    /**
     * Recherche plusieurs enregistrements par un champ spécifique
     *
     * @param string $field Champ de recherche
     * @param mixed $value Valeur recherchée
     * @param string $columns Colonnes à sélectionner
     * @return array<mixed>|object
     */
    public function findAllBy(string $field, mixed $value, string $columns = ''): array|object;

    /**
     * Compte le nombre total d'enregistrements
     *
     * @return int
     */
    public function count(): int;
}
