### BK-Transport

- **Imprtant**
    > Les explications des options `ApiPlatform` utilisés sont dans l'entité `User` et `Typepiece` qui est une référence pour les tables paramètres
    > On a utiliser l'authentification via le `jwt`
    > !! gérer le filtre du `identreprise` dans `EntrepriseScopeExtension`
    > Pour empêcher la suppression en `softDelete` lorsqu'un enregistrement est déjà lié à un autre mais ne fonctionne que sur `OneToMany` on n'a `HasSoftDeleteGuard` et `SoftDeleteProcessor`
    > Pour la récupération des données si on veut utiliser un `provider` tout en profitant de la gestion des filtres, pagination, tri, extensions.. native de `ApiPlatform` on a `InventaireProvider` dans lequel on s'est brancher au pipeline au lieu de le remplacer

- **Production**
    > On doit générer les clés jwt qui ne sont pas versionné
        > php bin/console lexik:jwt:generate-keypair
    > On peut désactiver la doc `ApiPlatform` dans `config/packages/api_platform.yaml`
    > On décomente la contrainte de l'url dans `ForgotPasswordInput`

- **Les modules**
    > Le module `Administration` : Entreprise - User - Role - Permission - UserRole
        > Gestion des comptes utilisateurs et de l'entreprise
        > Gestion et attribution des rôles
        > Gestion des permissions RBAC

    > Le module `Personnel` ou `RH` : Typepersonnel - Personnel - Detailpersonnel
        > Gestion des employés de la compagnie
        > Affectation d'un personnel à un voyage ou depannage via detail personnel
        > Historique des affectations avec les detail du personnel

    > Le module `Gestion de stock` & `Approvisionnement` : Typepiece - Marquepiece - Model - Fournisseur - Piece - Approvisionnement - Detailapprovisionnement - Inventaire
        > Gestion des des pièces détachées
        > Gestion des fournisseurs
        > Approvisionnement : Entrée des pièces en stock ou enregistrer un achat de pièces
            > On crée un approvisionnement et ses details approvisionnements ce qui génère un mouvement `ENTREE` dans `Inventaire` et met à jour le stock automatiquement
        > Dépannage : Sortie de stock.. voir module flotte
            > !! dépannage qui génère un mouvement `SORTIE` dans `Inventaire` et met à jour le stock automatiquement
        > Ajustement manuel pour corriger le stock et génère un mouvement `AJUSTEMENT` dans `Inventaire` et les inventaires sont en lecture seule `getCollection` et `get`
        > Alertes stock faible
        > Inventaire : Suivi de stock actuel et historique des mouvements

    > Le module `Flotte` & `Maintenance` : Marque - Car - Depannage - DetailDepannage
        > Gestion des cars
        > On crée un dépannage ce qui ajoute des détails dépannage et génère un mouvement `SORTIE` dans `Inventaire` et met à jour le stock
        > Affecter un personnel à un détail dépannage ex: mécaniciens
        > Historique des maintenances par véhicule

    > Le module `Exploitation` : Gare - Tarif - Trajet - Voyage
        > Gestion des gares, tarifs
        > Créer un Trajet et définir son tarif ce qui génère automatiquement le premier voyage du trajet
        > Gestion du voyage
            > Affecter un car disponible et du personnel via détail personnel à un voyage
            > Gérer horaires de départ et d'arrivée
        > Suivi du statut voyage
        > Historique complet pour reporting ou voyages par trajet et véhicule
        > Impression de bordereau qui est un document qui résume toutes les ventes de tickets d'un voyage dans une gare donnée, donc on a `Ticket` ManyToOne `Gare`

    > Le module `Billetterie` : Ticket
        > Gestion et émission des tickets pour un voyage
        > Calcul automatique du montant via le tarif
        > Suivi du nombre de places vendues et de la recette par voyage
        > Si on peut annuler un ticket on décrémente les places occupées du voyage

    > Le module `Tableau de bord` & `Rapports`
        > Exploitation
            > Nombre de voyages par période
            > Taux de remplissage
            > Voyages par statut 
        > Financier
            > Recettes billetterie
            > Coût des dépannages
            > Coût approvisionnements
        > Stock
            > Stock actuel par pièce
            > Pièces critiques
            > Mouvements récents
        > Flotte
            > Véhicules les plus en panne
            > Véhicules par état
            > Coût de maintenance par véhicule
- - 

    > Si un voyage est annuler (Car libéré → DISPO, places remboursées) :: Il y'a un piège ici, si on crée un voyage et qu'on le supprime avant de l'avoir clôturé on ne pourra pas affecté le car qu'il utilise sur autre voyage :: Mais on l'a géré via condition 'deletedAt' ce qui fais que le fait de remove correspond à anuller


### BK-Transport

- **Command**
    > php -S localhost:8000 -t public | symfony serve
    > php bin/console cache:clear
    > php bin/console debug:router
    > php bin/console make:controller
    > php bin/console make:entity
    > php bin/console make:voter
    > php bin/console make:listener
    > php bin/console make:subscriber
    > php bin/console make:fixtures
    > php bin/console doctrine:fixtures:load
    > php bin/console make:migration
    > php bin/console doctrine:migrations:migrate
    > php bin/console doctrine:schema:update --force : `--env=test` pour les tests
        > php bin/console doctrine:schema:update --dump-sql
    > php bin/console doctrine:fixtures:load : `--env=test` pour les tests
    > php bin/console translation:extract --dump-messages fr
    > php bin/console translation:extract --force fr --format=yaml
    > php bin/console make:test
    > php bin/console make:state-processor
    > php bin/console make:state-provider

- **Git**
    > git remote add origin git@github.com:Damo-dp45/Backend-Transport.git
    > git branch -M main
    > git push -u origin main


- Le module `Courrier` : Tarifcourrier, Courrier, Detailcourrier
    > Pour calculer la taxe d'un colis `Detailcourrier` on se base sur valeur, à la création on cherche le `TarifCourrier` dont `valeur_min <= valeur <= valeur_max` et on affecte son `montanttaxe` ou `montant` du colis
    > On a géré le tarif des colis via un système de `grille tarifaire` ou tranches `10 001 - 50 000 FCFA → taxe fixe 3 000` et on peut le faire aussi avec le poids du colis `k`
    > Pour que le `statut` du courrier suit automatiquement le voyage on a `CourrierStatutSubscriber` qui gère la transition `EN_TRANSIT → RECEPTIONNE` qui correspond à l'accusé de réception à la gare d'arrivée qui confirme l'arrivée des colis
    > Pour la transition du statut `RECEPTIONNE → LIVRE` qui correspond à la remise au destinataire avec potentiellement un paiement, c'est l'agent de la gare d'arrivée qui confirme la remise au destinataire via l'endpoint `../livrer`
    > Pour le paiement de la taxe on a 2 types, à l'envoi ou à la reception du courrier

- Le module `Bagage` : Tarifbagage, Bagage
    > On a 2 façon de faire
        > Le modèle `A` déclaration à l'achat qui permet au client de déclarer ses bagages en achetant son ticket de voyage. Le prix est calculé et inclus immédiatement
        > !! `B` facturation au chargement qui au chargement du car les bagages du client sont pesés physiquement et un ticket de pesée séparé est émis qui est un reçu distinct du ticket de voyage qui documente le poids, la nature et le coût des bagages.. et lie le bagage au client
    > Le tarif du bagage est basée sur le poids
    > Pour gérer l'automatisation du statut du bagage on a `BagageStatutSubscriber` qui écoute les changements sur `Voyage` et va causer un soucis si on a clôturé le voyage avant de déclarer que le bagage est perdu







On a ajouter 2 fonctionnalités dans l'application côté backend et frontend qui sont

...

Donc je me dis que ça impacte les statistiques Financier, dit moi lesquelles. Aussi voici des remarques :

- Le montant qui entre dans la caisse à la réception doit être tracé, c'est une **recette** comme les tickets. Dans le stats Financier ça entre dans les recettes billetterie/courrier
    - Recettes bagages → SUM(Bagage.montant) par période/voyage
    - Recettes Courrier → SUM(Courrier.montant) par période/gare

### C — Inventaire des colis à la réception

C'est un document que le chauffeur remet à l'agent de la gare d'arrivée listant tous les colis embarqués sur son voyage. Dans ta structure actuelle tu peux déjà générer ça depuis :
```
Detailcourrier JOIN Courrier WHERE voyage.id = X AND courrier.statut IN ('EN_TRANSIT', 'RECEPTIONNE')
```
Pas besoin d'une nouvelle entité — c'est juste un **endpoint de rapport** ou une **impression** comme le bordereau voyage.

### D — Bordereau chauffeur

C'est l'extension naturelle de ton bordereau voyage existant (`Ticket ManyToOne Gare`) — il suffit d'y ajouter la liste des courriers embarqués sur le voyage. Même logique, même endpoint d'impression.







- On vas faire la gestion des statuts
    > Sur `Personnel` ajouter `statut` (ACTIF, SUSPENDU) si on a une système de suspension et `date_embauche`
    > Quand un dépannage est créé → le statut du véhicule doit passer automatiquement à EN_PANNE et ça devra être géré via EventSubscriber ou Service métier et le reste de la logique du genre si le dépannage est clôturé le statut du car repasse en état DISPONIBLE (ou EN_MISSION si voyage en attente), etc..
    > Un véhicule en mission ou panne ne peut pas être affecté à un nouveau voyage. Le statut du Car est le verrou.







## Oui, c'est la meilleure optimisation

Actuellement tu fais **2 requêtes** : une pour les inventaires, une pour les users via `findInfosByIds`. Avec la jointure dans l'extension Doctrine, tu n'en feras plus qu'**une seule**.

---

## Où mettre la jointure

Pas dans le provider directement, mais dans une **extension de requête** API Platform :

```php
<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Inventaire;
use Doctrine\ORM\QueryBuilder;

class InventaireExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->addJoins($resourceClass, $queryBuilder);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->addJoins($resourceClass, $queryBuilder);
    }

    private function addJoins(string $resourceClass, QueryBuilder $qb): void
    {
        if ($resourceClass !== Inventaire::class) {
            return;
        }

        $rootAlias = $qb->getRootAliases()[0];

        $qb->leftJoin("{$rootAlias}.piece", 'p')
           ->addSelect('p')
           ->leftJoin("{$rootAlias}.createdBy", 'u')
           ->addSelect('u');
    }
}
```

J'ai ajouté `piece` en même temps puisque tu l'utilises aussi dans `toOutput` via `getPiece()->getLibelle()`.

---

## Provider simplifié

Avec la jointure, les users et les pièces sont déjà hydratés par Doctrine, donc **plus besoin de `findInfosByIds`** :

```php
public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
{
    $isCollection = $operation instanceof GetCollection;

    $data = $isCollection
        ? $this->collectionProvider->provide($operation, $uriVariables, $context)
        : $this->itemProvider->provide($operation, $uriVariables, $context)
    ;

    if ($isCollection) {
        $mapped = array_map(
            fn(Inventaire $i) => $this->toOutput($i),
            iterator_to_array($data)
        );

        return new TraversablePaginator(
            new \ArrayIterator($mapped),
            $data->getCurrentPage(),
            $data->getItemsPerPage(),
            $data->getTotalItems()
        );
    }

    if (!$data instanceof Inventaire) {
        return null;
    }

    return $this->toOutput($data);
}

private function toOutput(Inventaire $inventaire): InventaireOutput
{
    $user = $inventaire->getCreatedByUser(); // relation hydratée

    return new InventaireOutput(
        id: $inventaire->getId(),
        typemouvement: $inventaire->getTypemouvement(),
        referencetype: $inventaire->getReferenceType(),
        referenceid: $inventaire->getReferenceId(),
        quantite: $inventaire->getQuantite(),
        datemouvement: $inventaire->getDatemouvement()?->format('Y-m-d H:i') ?? '',
        createdAt: $inventaire->getCreatedAt()?->format('Y-m-d H:i') ?? '',
        pieceName: $inventaire->getPiece()?->getLibelle(),
        createdBy: $inventaire->getCreatedBy(),
        createdByNom: $user?->getNom(),
        createdByPrenom: $user?->getPrenom()
    );
}
```

Tu peux aussi supprimer `UserRepository` du constructeur du provider puisqu'il n'est plus nécessaire.

---

## Résultat

| | Avant | Après |
|---|---|---|
| Requêtes SQL | 2 (inventaires + users) | 1 (tout jointé) |
| Complexité provider | Élevée | Simple |
| Risque N+1 sur `piece` | ✅ Éliminé aussi | ✅ |








- - 
Parfait 🔥 on va construire un **système d’alertes intelligent** pour ton app — et là on passe en mode **produit pro**.

---

# 🚨 🎯 OBJECTIF DU MODULE ALERTES

Notifier automatiquement :

* 📦 stock faible
* 🚍 voyage complet / presque complet
* 🛠️ anomalies (optionnel plus tard)

---

# 🧠 🧩 1. CONCEPTION GLOBALE

On crée une entité centrale :

## 👉 `Alerte`

Elle servira pour **TOUT le système**

---

# 🧱 2. ENTITY `Alerte`

```php
#[ORM\Entity]
class Alerte extends EntityBase implements EntrepriseOwnedInterface
{
    #[ORM\Column(length: 50)]
    private string $type; // STOCK_FAIBLE, VOYAGE_COMPLET...

    #[ORM\Column(length: 255)]
    private string $message;

    #[ORM\Column(nullable: true)]
    private ?int $referenceId = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $referenceType = null;

    #[ORM\Column]
    private bool $isRead = false;

    #[ORM\Column]
    private int $identreprise;
}
```

---

# 🧠 TYPES D’ALERTES

```php
class AlerteType
{
    public const STOCK_FAIBLE = 'STOCK_FAIBLE';
    public const VOYAGE_COMPLET = 'VOYAGE_COMPLET';
    public const VOYAGE_BIENTOT_COMPLET = 'VOYAGE_BIENTOT_COMPLET';
}
```

---

# 🧠 🧩 3. SERVICE CENTRAL (TRÈS IMPORTANT)

👉 pour éviter du code partout

```php
class AlerteService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function create(
        string $type,
        string $message,
        int $entrepriseId,
        ?int $referenceId = null,
        ?string $referenceType = null
    ): void {
        $alerte = new Alerte();
        $alerte
            ->setType($type)
            ->setMessage($message)
            ->setIdentreprise($entrepriseId)
            ->setReferenceId($referenceId)
            ->setReferenceType($referenceType);

        $this->em->persist($alerte);
    }
}
```

---

# 📦 4. ALERTES STOCK FAIBLE

---

## 🎯 RÈGLE

```txt
stock <= seuil
```

👉 ajoute dans `Piece` :

```php
private int $seuilAlerte = 5;
```

---

## 🧠 DANS TON `StockmouvementService`

👉 après chaque mouvement :

```php
if ($piece->getStock() <= $piece->getSeuilAlerte()) {

    $this->alerteService->create(
        AlerteType::STOCK_FAIBLE,
        "Stock faible pour {$piece->getLibelle()}",
        $entrepriseId,
        $piece->getId(),
        'PIECE'
    );
}
```

---

# 🚍 5. ALERTES VOYAGE

---

## 🎯 CAS 1 : VOYAGE COMPLET

Dans `TicketProcessor` :

```php
if ($voyage->getPlacesOccupees() == $voyage->getPlacesTotal()) {

    $this->alerteService->create(
        AlerteType::VOYAGE_COMPLET,
        "Voyage {$voyage->getCode()} complet",
        $entrepriseId,
        $voyage->getId(),
        'VOYAGE'
    );
}
```

---

## 🎯 CAS 2 : PRESQUE COMPLET

```php
$taux = ($voyage->getPlacesOccupees() / $voyage->getPlacesTotal()) * 100;

if ($taux >= 80) {
    $this->alerteService->create(
        AlerteType::VOYAGE_BIENTOT_COMPLET,
        "Voyage bientôt complet ({$taux}%)",
        $entrepriseId,
        $voyage->getId(),
        'VOYAGE'
    );
}
```

---

# 🧱 6. API PLATFORM (ENDPOINTS)

```php
#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    operations: [

        new GetCollection(
            security: "is_granted('VOIR', 'Alerte')"
        ),

        new Get(),

        new Patch(
            security: "is_granted('MODIFIER', object)"
        )
    ]
)]
```

---

# 📊 7. DASHBOARD "ALERTES NON LUES"

👉 endpoint :

```http
GET /alertes?isRead=false
```

---

## 🔥 BONUS FILTRE

```php
#[ApiFilter(SearchFilter::class, properties: [
    'isRead' => 'exact',
    'type' => 'exact'
])]
```

---

# 🧱 8. MARQUER COMME LU

👉 PATCH :

```json
{
  "isRead": true
}
```

---

# 💡 9. AMÉLIORATIONS PRO

---

## 🔥 1. Éviter spam alertes

👉 ex stock faible → une seule alerte active

```php
check if already exists non lue
```

---

## 🔥 2. Priorité

```php
priority (LOW, MEDIUM, HIGH)
```

---

## 🔥 3. Notification temps réel

* WebSocket
* ou polling `/alertes`

---

## 🔥 4. Notification WhatsApp / Email

👉 futur upgrade 🚀

---

# 🧠 CONCLUSION

Tu viens de créer :

## 🚨 système d’alertes complet

* 📦 stock
* 🚍 voyages
* 📊 dashboard
* 🔒 multi-entreprise

---

# 🚀 PROCHAINE ÉTAPE

👉 maintenant tu peux faire :

## 📊 DASHBOARD GLOBAL

* recettes
* voyages
* pannes
* top pièces

---

Dis-moi :
👉 **"dashboard"** et on fait un dashboard digne d’une startup 🚀🔥















- Salut claude, on vas développer une application symfony de compagnie de transport mutli-entreprise mais en architecture séparé, on fera d'abord le backend (Symfony, ApiPlatform, LexikJwtBundle, refreshTokenBundle) et je t'envoi les tables qu'on m'a donné

User
    nom
    prenom
    email
    password
    roles
    avatar
    etat default true
    entreprise ManyToOne

Entreprise
    libelle
    contact1
    contact2
    adresse
    email
    anneecreation
    sigle
    siteweb
    image
    rccm
    banque
    type
    centreimpot
    tauxtva

Fournisseur
    libelle
    contact
    nom
    email
    adresse
    pays
    identreprise INT

Approvisionnement
    date_appro
    fournisseur ManyToOne
    identreprise INT

Detailapprovisionnement
    quantite
    prix_unitaire
    approvisionnement ManyToOne
    piece ManyToOne
    couttotal

Typepiece
    libelle UNIQUE par identreprise
    identreprise INT

Marquepiece
    libelle UNIQUE par identreprise
    identreprise INT

Model
    libelle UNIQUE par identreprise
    identreprise INT

Piece
    libelle
    image
    stockinitial
    prix_unitaire
    seuilstock
    typepiece ManyToOne NULL
    marquepiece ManyToOne NULL
    model ManyToOne NULL
    identreprise INT

Depannage
    date_depannage
    lieu_depannage
    description
    identreprise INT
    car ManyToOne
    couttotal

Detaildepannage
    quantite
    depannage ManyToOne
    piece ManyToOne
    prixunitaire

Typepersonnel
    libelle UNIQUE par identreprise
    identreprise INT

Personnel
    nom
    prenom
    contact
    code
    image
    typepersonnel ManyToOne
    identreprise INT

Detailpersonnel
    motif
    personnel ManyToOne
    depannage ManyToOne NULL
    voyage ManyToOne NULL

Marque
    libelle UNIQUE par identreprise
    identreprise INT

Typevehicule
    libelle UNIQUE par identreprise
    identreprise INT

Modelvehicule
    libelle UNIQUE par identreprise
    identreprise INT

Car
    matricule UNIQUE par identreprise
    nbr_siege
    date_arrivee
    etat
    identreprise INT
    marque ManyToOne NULL
    typevehicule ManyToOne NULL
    modelvehicule ManyToOne NULL
    sieges_gauche INT
    sieges_droite INT

Siege
    numero
    rangee
    colonne
    cote
    car ManyToOne
    identreprise
    statut -- Champ virtuel non persisté

Tarif
    libelle
    montant
    identreprise INT

Trajet
    provenance
    destination
    code_trajet
    order_index
    tarif ManyToOne
    identreprise INT

Voyage
    provenance
    destination
    date_debut
    date_fin
    code_voyage
    trajet ManyToOne
    car ManyToOne NULL
    identreprise INT
    placestotal -- Pour contrôler la vente
    placesoccupees -- Pour contrôler la vente

Gare
    chef_gare
    ville
    libelle
    description
    contact1
    contact2
    identreprise INT

Ticket
    prix
    codeticket
    nomclient
    contactclient
    voyage ManyToOne
    identreprise INT
    siege ManyToOne

Inventaire
    piece ManyToOne
    type_mouvement (ENTREE, SORTIE, AJUSTEMENT)
    quantite
    date_mouvement
    reference_type (APPROVISIONNEMENT, DEPANNAGE, AJUSTEMENT)
    reference_id
    identreprise INT

Role
    name UNIQUE par identreprise
    description
    typerole
    identreprise INT

Permission
    entity (Gare, Voyage..)
    action (VIEW, CREATE, EDIT, DELETE..)
    identreprise INT
    role ManyToOne

UserRole
    user ManyToOne
    role ManyToOne
    identreprise INT

- J'ai un EntityBase qui contient createdAt, updatedAt et deletedAt et qui se fait étendre par les autres entités sauf les Detail.. et User

- Remarque :
    > Plusieurs entités de l'appli sont liées à l'entreprise avec 'identreprise' qui est un int sauf le 'User' qui est un 'ManyToOne' ensuite pour récupérer un enregistrement on vérifie si son 'identreprise' corrspond à l'entreprise de l'utilisateur

Avant de commencer à coder liste tous les modules de l'application