<p align="center">
  <img src="component/media/img/aaj-banner.svg" alt="AAJ - Amazon Affiliate Joomla" width="420">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/version-1.0.1-blue" alt="Version 1.0.1">
  <img src="https://img.shields.io/badge/Joomla-6-1A3867?logo=joomla&logoColor=white" alt="Joomla 6">
  <img src="https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white" alt="PHP 8.3+">
  <img src="https://img.shields.io/badge/Amazon-Creators%20API-FF9900?logo=amazon&logoColor=white" alt="Amazon Creators API">
  <img src="https://img.shields.io/badge/licence-GPL%20v2-green" alt="Licence GPL v2">
</p>

# AAJ - Amazon Affiliate Joomla

AAJ affiche des produits Amazon dans les articles Joomla à partir de votre identifiant Partenaires Amazon : fiches produit, liens texte, listes de meilleures ventes et de nouveautés. Les données (titre, prix, image, note, Prime) viennent de l'**Amazon Creators API**, le successeur de la PA-API 5, et sont mises en cache dans la base Joomla.

Le projet est un portage pour Joomla 6 du plugin WordPress AAWP, aligné sur sa version 5.1.2.

## Sommaire

- [Fonctionnalités](#fonctionnalités)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation : les shortcodes](#utilisation--les-shortcodes)
- [Cache](#cache)
- [Structure du dépôt](#structure-du-dépôt)
- [Construire les paquets](#construire-les-paquets)
- [Tests](#tests)
- [Limites connues](#limites-connues)
- [Licence](#licence)

## Fonctionnalités

- Fiches produit `box` en trois gabarits : horizontal, vertical, liste. Plusieurs ASIN possibles dans un même shortcode.
- Liens texte affiliés (`link`) et champs isolés (`field`) : prix, titre, URL, note, nombre d'avis, Prime, image.
- Listes de meilleures ventes (`bestseller`) et de nouveautés (`new_releases`), par catégorie Amazon (browse node) ou par mots-clés, jusqu'à 10 produits.
- Bouton « AAJ » dans l'éditeur d'articles : recherche de produits par mot-clé via l'API et insertion du shortcode.
- Back-office : tableau de bord, produits en cache, listes en cache (suppression, purge), réglages avec bouton de test de connexion.
- 22 marketplaces Amazon (FR, UK, DE, IT, ES, NL, BE, IE, PL, SE, TR, AE, SA, EG, IN, US, CA, MX, BR, JP, SG, AU).
- Identifiants Creators API v3 (Login with Amazon) et v2 (Cognito), version détectée automatiquement.
- Liens sortants en `rel="nofollow noopener sponsored"` et texte de mention légale affilié configurable.
- Interface en français et en anglais.

## Prérequis

| Élément | Version |
|---|---|
| Joomla | 6.x |
| PHP | 8.3 ou plus (minimum requis par Joomla 6) |
| Base de données | MySQL / MariaDB en `utf8mb4` |
| Compte Amazon | Partenaires Amazon (Associates) avec accès à la Creators API |

Il vous faut trois informations côté Amazon : le **Client ID** et le **Client Secret** de la Creators API (générés dans Associates Central), et votre **tag partenaire** (par exemple `monsite-21`).

## Installation

AAJ se compose de quatre extensions. Installez-les dans cet ordre via *Système → Installer → Extensions* :

| # | Paquet | Type | Rôle |
|---|---|---|---|
| 1 | `com_aaj` | Composant | Back-office, client API, tables, CSS/JS. **Obligatoire, à installer en premier** : les plugins chargent ses classes. |
| 2 | `plg_content_aaj` | Plugin de contenu | Transforme les shortcodes `{aaj ...}` en HTML. |
| 3 | `plg_editors-xtd_aaj` | Plugin bouton d'éditeur | Ajoute le bouton « AAJ » sous l'éditeur. |
| 4 | `mod_aaj_products` | Module de site | Facultatif, voir [Limites connues](#limites-connues). |

Les paquets prêts à installer sont dans [`dist/`](dist/) (ou dans les *Releases* GitHub). Pour les construire vous-même, voir [Construire les paquets](#construire-les-paquets).

Après l'installation, allez dans *Système → Plugins* et activez **AAJ - Content Plugin** et le bouton d'éditeur AAJ.

L'installation du composant crée deux tables : `#__aaj_products` (cache des produits) et `#__aaj_lists` (cache des listes). Elles sont supprimées à la désinstallation.

## Configuration

Ouvrez *Composants → AAJ → Settings* (l'écran est libellé en anglais).

| Réglage | Description | Défaut |
|---|---|---|
| Client ID / Client Secret | Identifiants Creators API. Un Client ID qui commence par `amzn` (format `amzn1.application-oa2-client...`) est traité comme un identifiant v3, sinon v2. | — |
| Associate Tag | Votre tag partenaire, ajouté à tous les liens. | — |
| Amazon Store | Marketplace Amazon utilisée pour les requêtes et les liens. | `com` |
| Cache Duration | En heures. Au-delà, le produit est redemandé à l'API au prochain affichage. | `720` (30 jours) |
| Title Length | Nombre de caractères avant coupure du titre. | `100` |
| Button Style | `amazon` (dégradé orange) ou `standard`. | `amazon` |
| Default Template | Gabarit des fiches : `horizontal`, `vertical` ou `list`. | `horizontal` |
| Disclaimer | Texte affiché sous chaque bloc de produits (par exemple « En tant que Partenaire Amazon, je réalise un bénéfice sur les achats remplissant les conditions requises »). | vide |

Le bouton **Test Connection** vérifie les identifiants avant enregistrement.

## Utilisation : les shortcodes

Les shortcodes s'écrivent directement dans le texte d'un article, ou s'insèrent avec le bouton AAJ de l'éditeur. Les guillemets simples ou doubles sont acceptés, et les accolades encodées par certains éditeurs (JCE par exemple) sont gérées.

### Fiche produit

```text
{aaj type="box" asin="B08N5WRWNW"}
{aaj type="box" asin="B08N5WRWNW,B07FZ8S74R" template="vertical"}
```

`id` est accepté comme alias de `asin`. Si un ASIN n'est pas trouvé par l'API, une fiche de repli s'affiche avec un simple lien vers la page Amazon.

### Lien texte

```text
{aaj type="link" asin="B08N5WRWNW"}
{aaj type="link" asin="B08N5WRWNW" title="Voir ce casque sur Amazon"}
```

Sans `title`, le texte du lien est le titre du produit.

### Meilleures ventes et nouveautés

```text
{aaj type="bestseller" category="13921051" items="5"}
{aaj type="bestseller" keywords="casque bluetooth" items="10" template="list"}
{aaj type="new_releases" category="13921051"}
```

`category` accepte un identifiant de catégorie Amazon (browse node, numérique) ou des mots-clés. `keywords` est un alias. `items` vaut 10 par défaut et ne peut pas dépasser 10.

### Champ isolé

Pour glisser une information dans une phrase :

```text
Il coûte actuellement {aaj type="field" asin="B08N5WRWNW" field="price"}.
```

| `field` | Résultat |
|---|---|
| `price` | Devise et prix |
| `title` | Titre du produit |
| `url` | URL affiliée |
| `rating` | Note moyenne |
| `reviews` | Nombre d'avis |
| `prime` | `Yes` ou `No` |
| `image` | Balise `<img>` ; ajoutez `size="small"`, `"medium"` (défaut) ou `"large"` |

### Récapitulatif des attributs

| Attribut | Types concernés | Valeurs |
|---|---|---|
| `type` | tous | `box` (défaut), `link`, `bestseller`, `new_releases`, `field` |
| `asin` / `id` | `box`, `link`, `field` | Un ASIN, ou plusieurs séparés par des virgules pour `box` (`field` n'accepte que `asin`) |
| `template` | `box`, `bestseller`, `new_releases` | `horizontal`, `vertical`, `list` |
| `title` | `link` | Texte du lien |
| `category` / `keywords` | `bestseller`, `new_releases` | Browse node ou mots-clés |
| `items` | `bestseller`, `new_releases` | 1 à 10 |
| `field`, `size` | `field` | Voir le tableau ci-dessus |

## Cache

Chaque produit récupéré est enregistré dans `#__aaj_products`, chaque liste dans `#__aaj_lists`. Le rafraîchissement est paresseux : quand une page affiche un produit dont le cache a dépassé la durée configurée, AAJ interroge l'API à ce moment-là. Il n'y a pas de tâche planifiée.

Pour forcer une mise à jour, supprimez le produit ou la liste dans *Composants → AAJ → Products* ou *Lists* ; ils seront rechargés au prochain affichage.

## Structure du dépôt

```text
component/                  com_aaj
├── aaj.xml                 manifeste
├── admin/
│   ├── classes/
│   │   ├── Api/AmazonApi.php           client Creators API (jeton OAuth, getItems, searchItems, listes)
│   │   ├── Product/AajProduct.php      conversion réponse API -> ligne de cache, accès aux champs
│   │   ├── Helper/RenderHelper.php     helpers utilisés par les gabarits
│   │   ├── Helper/ConfigHelper.php
│   │   ├── Cache/CacheManager.php
│   │   └── Database/                   tables produits et listes
│   ├── controllers/        dashboard, products, lists, settings, api (recherche)
│   ├── views/              dashboard, products, lists, settings, asinpicker
│   ├── sql/                install / uninstall
│   └── language/           en-GB, fr-FR
├── media/                  css, js, images (installés dans media/com_aaj)
└── site/aaj.php
plugin/                     plg_content_aaj (shortcodes + gabarits tmpl/{box,bestseller,new_releases})
plugin_xtd/                 plg_editors-xtd_aaj (bouton d'éditeur)
module/                     mod_aaj_products
tests/check_api.php         autotest sans Joomla
dist/                       paquets zip installables
```

Les classes du composant utilisent l'espace de noms `Aaj\Component\Aaj\Administrator\`. Le plugin et le module enregistrent leur propre autoloader vers `administrator/components/com_aaj/classes`.

### Personnaliser l'affichage

Les gabarits HTML sont dans `plugin/tmpl/<type>/<gabarit>.php`. Chaque gabarit reçoit un objet `$r` (`RenderHelper`) avec `$r->item` (le produit `AajProduct`), `$r->atts` (attributs du shortcode) et `$r->type`. Les styles sont dans `component/media/css/main.css`, avec des classes BEM préfixées `aaj-` (`aaj-product`, `aaj-product__title`, `aaj-product__btn`...).

Un nom de gabarit inconnu retombe sur `horizontal`.

## Construire les paquets

Chaque zip contient le *contenu* du dossier de l'extension à sa racine (le manifeste XML doit être au premier niveau).

```bash
mkdir -p dist
(cd component  && zip -r ../dist/1_com_aaj_1.0.1.zip .)
(cd plugin     && zip -r ../dist/2_plg_content_aaj_1.0.1.zip .)
(cd module     && zip -r ../dist/3_mod_aaj_products_1.0.0.zip .)
(cd plugin_xtd && zip -r ../dist/4_plg_editors-xtd_aaj_1.0.0.zip .)
```

Sous Windows PowerShell :

```powershell
Compress-Archive -Path component\* -DestinationPath dist\1_com_aaj_1.0.1.zip -Force
```

Pensez à mettre à jour la balise `<version>` du manifeste concerné avant de publier.

## Tests

Un autotest vérifie la conversion des réponses de l'API (champs prix, remise, disponibilité, Prime, avis) et la logique de pagination des listes, sans installation Joomla :

```bash
php tests/check_api.php
```

Il affiche `OK` si tout passe.

## Limites connues

- **Module `mod_aaj_products`** : il s'installe mais n'affiche pas encore de produits. Son manifeste ne déclare aucun paramètre, donc il utilise toujours le gabarit `horizontal`, qui se limite à un message ; les gabarits `list`, `vertical` et `widget-small` sont écrits mais attendent des variables (`$items`, `$renderer`) que le module ne prépare pas. En attendant, utilisez les shortcodes dans un article ou un module « Contenu personnalisé » (avec le plugin de contenu activé).
- **Réglage « shortcode tag »** : il est enregistré mais pas encore lu par le plugin, qui reconnaît toujours `{aaj ...}`.
- **Textes de la fiche de repli** : « Voir sur Amazon », « Prix disponible sur Amazon » sont écrits en dur en français dans le plugin.
- **Rafraîchissement du cache** : uniquement à l'affichage, pas de tâche planifiée (voir [Cache](#cache)).
- La Creators API applique ses propres quotas et conditions d'accès. En cas d'échec, les erreurs sont écrites dans les journaux Joomla (catégorie `com_aaj`).

## Historique des versions

Voir [CHANGELOG.md](CHANGELOG.md).

## Licence

Distribué sous licence GNU GPL v2, voir [LICENSE](LICENSE).

AAJ n'est ni affilié à Amazon ni approuvé par Amazon. Amazon et le logo Amazon sont des marques d'Amazon.com, Inc. ou de ses filiales. Le respect du contrat d'exploitation du Programme Partenaires Amazon (mentions légales, usage des prix) reste à la charge de l'éditeur du site.
