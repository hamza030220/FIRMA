# Analyse de Performance — FIRMA Marketplace

## CRITIQUE (Priorité 1)

### 1. Dashboard Admin — 6 requêtes COUNT séparées
- **Fichier** : `src/Controller/Admin/MarketplaceController.php` (lignes 35-51)
- **Problème** : Chaque statistique (équipements, véhicules, terrains, fournisseurs, etc.) fait un `COUNT(*)` séparé → 6 requêtes SQL par chargement de page.
- **Fix suggéré** : Une seule requête SQL avec des sous-requêtes ou un `UNION`.

```php
// AVANT (6 requêtes)
$nbEquipements = $equipementRepo->count([]);
$nbVehicules = $vehiculeRepo->count([]);
$nbTerrains = $terrainRepo->count([]);
// ... x6

// APRÈS (1 requête)
$stats = $em->getConnection()->executeQuery("
    SELECT
        (SELECT COUNT(*) FROM equipements) as nb_equipements,
        (SELECT COUNT(*) FROM vehicules) as nb_vehicules,
        (SELECT COUNT(*) FROM terrains) as nb_terrains,
        (SELECT COUNT(*) FROM fournisseurs) as nb_fournisseurs,
        (SELECT COUNT(*) FROM equipements WHERE disponible = 1) as nb_disponibles,
        (SELECT COUNT(*) FROM equipements WHERE quantite_stock < seuil_alerte) as nb_alerte_stock
")->fetchAssociative();
```

---

### 2. Listes Admin — TOUS les enregistrements chargés sans pagination
- **Fichiers** :
  - `src/Controller/Admin/MarketplaceController.php` (méthodes list pour équipements, véhicules, terrains, fournisseurs)
- **Problème** : Les méthodes `findBy()` / `findAll()` chargent toutes les lignes d'un coup. Si la table grandit, la page devient très lente.
- **Fix suggéré** : Utiliser `KnpPaginatorBundle` ou pagination manuelle avec `LIMIT/OFFSET`.

```php
// AVANT
$equipements = $equipementRepo->findBy(['disponible' => true]);

// APRÈS (avec KnpPaginator)
$query = $equipementRepo->createQueryBuilder('e')
    ->where('e.disponible = true')
    ->orderBy('e.dateCreation', 'DESC')
    ->getQuery();

$equipements = $paginator->paginate($query, $request->query->getInt('page', 1), 20);
```

---

### 3. Problème N+1 dans les templates Admin
- **Fichiers** :
  - `templates/admin/marketplace/*.html.twig` (toutes les listes)
  - `src/Controller/Admin/MarketplaceController.php`
- **Problème** : Dans les boucles Twig, `e.categorie.nom` et `e.fournisseur.nom` déclenchent une requête SQL supplémentaire **par ligne** affichée. Avec 100 équipements → 200 requêtes supplémentaires.
- **Fix suggéré** : Eager-loading avec `leftJoin` + `addSelect` dans les requêtes.

```php
// AVANT (N+1 queries)
$equipements = $repo->findAll();
// Puis dans Twig: {{ e.categorie.nom }} → 1 requête par ligne

// APRÈS (1 seule requête avec jointures)
$equipements = $repo->createQueryBuilder('e')
    ->leftJoin('e.categorie', 'c')->addSelect('c')
    ->leftJoin('e.fournisseur', 'f')->addSelect('f')
    ->getQuery()
    ->getResult();
```

---

## IMPORTANT (Priorité 2)

### 4. Index manquants en base de données
- **Fichiers** : Entités dans `src/Entity/Marketplace/`
- **Problème** : Pas d'index sur `categorie_id`, `fournisseur_id`, `disponible`, `dateCreation` → scans complets de table à chaque requête filtrée.
- **Fix suggéré** : Ajouter des annotations `@ORM\Index` dans les entités puis générer une migration.

```php
// Dans l'entité Equipement
#[ORM\Table(name: 'equipements')]
#[ORM\Index(columns: ['categorie_id'], name: 'idx_categorie')]
#[ORM\Index(columns: ['fournisseur_id'], name: 'idx_fournisseur')]
#[ORM\Index(columns: ['disponible'], name: 'idx_disponible')]
#[ORM\Index(columns: ['date_creation'], name: 'idx_date_creation')]
class Equipement
```

Puis :
```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

---

### 5. Marketplace User — TOUS les items chargés
- **Fichier** : `src/Controller/User/MarketplaceController.php` (lignes 37-49)
- **Problème** : `findBy(['disponible' => true])` charge **tous** les équipements, véhicules et terrains disponibles d'un coup. C'est la cause principale de la lenteur du marketplace côté utilisateur.
- **Fix suggéré** : Pagination côté serveur.

```php
// AVANT
$equipements = $equipRepo->findBy(['disponible' => true]);
$vehicules = $vehiculeRepo->findBy(['disponible' => true]);
$terrains = $terrainRepo->findBy(['disponible' => true]);

// APRÈS
$page = $request->query->getInt('page', 1);
$limit = 12;

$equipements = $equipRepo->createQueryBuilder('e')
    ->where('e.disponible = true')
    ->leftJoin('e.categorie', 'c')->addSelect('c')
    ->setFirstResult(($page - 1) * $limit)
    ->setMaxResults($limit)
    ->getQuery()
    ->getResult();
```

---

### 6. Repositories vides — aucune méthode custom
- **Fichiers** : Tous les repositories dans `src/Repository/`
- **Problème** : Les 7 repositories marketplace n'ont aucune méthode personnalisée. Tout passe par `findAll()`, `findBy()`, `find()` — pas de jointures, pas de pagination, pas d'optimisation.
- **Fix suggéré** : Créer des méthodes dédiées avec `QueryBuilder`.

```php
// Dans EquipementRepository
public function findPaginatedWithRelations(int $page, int $limit = 20): array
{
    return $this->createQueryBuilder('e')
        ->leftJoin('e.categorie', 'c')->addSelect('c')
        ->leftJoin('e.fournisseur', 'f')->addSelect('f')
        ->orderBy('e.dateCreation', 'DESC')
        ->setFirstResult(($page - 1) * $limit)
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}

public function countAll(): int
{
    return (int) $this->createQueryBuilder('e')
        ->select('COUNT(e.id)')
        ->getQuery()
        ->getSingleScalarResult();
}
```

---

## MOYEN (Priorité 3)

### 7. Panier — requête par article dans une boucle
- **Fichier** : `src/Controller/User/MarketplaceController.php` (gestion du panier)
- **Problème** : Chaque item du panier fait un `find($id)` séparé au lieu d'un batch.
- **Fix suggéré** :

```php
// AVANT
foreach ($cart as $id => $qty) {
    $item = $repo->find($id);  // 1 requête par item
}

// APRÈS
$ids = array_keys($cart);
$items = $repo->findBy(['id' => $ids]);  // 1 seule requête
```

---

### 8. Pas de cache Doctrine en dev
- **Fichier** : `config/packages/doctrine.yaml`
- **Problème** : Aucun cache de requêtes configuré. Chaque requête DQL est re-parsée à chaque exécution.
- **Fix suggéré** : Activer le cache au moins pour le metadata et les requêtes.

```yaml
# config/packages/doctrine.yaml
doctrine:
    orm:
        metadata_cache_driver:
            type: pool
            pool: doctrine.system_cache_pool
        query_cache_driver:
            type: pool
            pool: doctrine.system_cache_pool
```

---

### 9. Analyse de stock charge tout en mémoire
- **Fichier** : `src/Controller/Admin/MarketplaceController.php` (lignes 130-150)
- **Problème** : `findAll()` suivi d'un `array_filter()` en PHP pour déterminer les items en alerte stock. Charge tous les équipements en mémoire inutilement.
- **Fix suggéré** :

```php
// AVANT
$all = $repo->findAll();
$lowStock = array_filter($all, fn(Equipement $e) => $e->getQuantiteStock() < $e->getSeuilAlerte());

// APRÈS (direct en SQL)
$lowStock = $repo->createQueryBuilder('e')
    ->where('e.quantiteStock < e.seuilAlerte')
    ->getQuery()
    ->getResult();
```

---

### 10. Tables Admin — pagination client-side uniquement
- **Fichier** : `assets/marketplace.js`
- **Problème** : Tous les éléments sont rendus dans le DOM HTML, puis paginés en JavaScript côté client. Le navigateur doit traiter tout le HTML même si on n'affiche que 10 lignes.
- **Fix suggéré** : Implémenter la pagination côté serveur (voir point 2) et n'envoyer que les données de la page courante.

---

### 11. Images sans lazy loading
- **Fichier** : `templates/user/marketplace/index.html.twig` (ligne 74-76)
- **Problème** : Toutes les images se chargent immédiatement → temps de chargement initial élevé.
- **Fix suggéré** :

```twig
{# AVANT #}
<img src="{{ asset(e.imageUrl) }}" alt="{{ e.nom }}">

{# APRÈS #}
<img src="{{ asset(e.imageUrl) }}" alt="{{ e.nom }}" loading="lazy">
```

---

## Ordre de priorité recommandé

| # | Action | Gain estimé | Difficulté |
|---|--------|-------------|------------|
| 1 | Pagination serveur (admin + user marketplace) | Très élevé | Moyenne |
| 2 | Eager-loading des relations (N+1) | Très élevé | Facile |
| 3 | Index base de données | Élevé | Facile |
| 4 | Fusionner les COUNT du dashboard | Moyen | Facile |
| 5 | Lazy loading images | Moyen | Facile |
| 6 | Méthodes custom dans les repositories | Moyen | Moyenne |
| 7 | Batch fetch pour le panier | Moyen | Facile |
| 8 | Cache Doctrine | Moyen | Facile |
| 9 | Requête SQL pour alerte stock | Faible | Facile |
| 10 | Pagination serveur tables admin | Faible | Moyenne |
