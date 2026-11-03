# Changelog

Les versions suivent le [versionnage sémantique](https://semver.org/lang/fr/).

## [1.0.1] - 2026-11-03

Alignement sur AAWP 5.1.2 (corrections Creators API). Concerne `com_aaj` et `plg_content_aaj` ; le module et le bouton d'éditeur restent en 1.0.0.

### Corrigé

- Listes meilleures ventes / nouveautés : passage par `SearchItems` avec pagination `itemPage` et tri `NewestArrivals`. `GetBrowseNodes` ne fournit pas de ressource « top sellers » ni « new releases ».
- Lecture des produits alignée sur les modèles du SDK Creators API : `price.money`, `price.savings`, `price.savingBasis`, `customerReviews.count`.
- Disponibilité : prise en compte du premier listing qui en indique une, les statuts `IN_STOCK*` donnent « en stock ».
- Détection Prime revue.
- `getItems` garde les produits valides quand la réponse contient aussi des erreurs partielles.
- Version des identifiants déduite de leur format (`amzn...` = v3 Login with Amazon, sinon v2 Cognito).
- Plus d'écriture des colonnes `binding` et `product_group`, absentes du schéma d'installation.

### Sécurité

- Le point d'entrée d'administration vérifie la permission `core.manage`.

### Ajouté

- Attribut `keywords` pour les shortcodes de listes (alias de `category`).
- Autotest `tests/check_api.php`, exécutable sans Joomla.
- Paquets installables dans `dist/`.

### Modifié

- Textes de l'interface : mentions de la PA-API remplacées par la Creators API.

## [1.0.0]

Première version : composant `com_aaj`, plugin de contenu, bouton d'éditeur et module.

[1.0.1]: https://github.com/Dano7762/amazon-affiliate-joomla/compare/f7f5a32...042c0b5
[1.0.0]: https://github.com/Dano7762/amazon-affiliate-joomla/commit/f7f5a32
