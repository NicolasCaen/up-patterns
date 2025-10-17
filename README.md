# Up Patterns

Importe et centralise les patterns, templates et template parts d’un thème WordPress dans un plugin dédié avec leurs assets associés. Chaque import alimente un `manifest.json` compatible avec le plugin `up-bulk-plugins-installer` afin de redistribuer facilement la bibliothèque de patterns.

## Sommaire
- [Fonctionnalités](#fonctionnalités)
- [Installation](#installation)
- [Configuration](#configuration)
- [Importer un élément](#importer-un-élément)
- [Structure du dossier `ressources/`](#structure-du-dossier-ressources)
- [Manifest JSON généré](#manifest-json-généré)
- [Compatibilité avec up-bulk-plugins-installer](#compatibilité-avec-up-bulk-plugins-installer)
- [Répertoires d’assets supportés](#répertoires-dassets-supportés)
- [Limitations](#limitations)

## Fonctionnalités
- Liste les patterns (`patterns/`), templates (`templates/`) et template parts (`parts/`) du thème actif.
- Import unitaire de chaque fichier dans le plugin (`ressources/{type}/{slug}/`).
- Copie automatique des assets associés (CSS/SCSS, JS, GSAP) ainsi que l’aperçu image si présent.
- Génération/actualisation du `manifest.json` à la racine du plugin pour chaque import.
- Paramétrage des répertoires sources via l’interface admin.

## Installation
1. Copier le dossier du plugin dans `wp-content/plugins/up-patterns/`.
2. Activer le plugin depuis `Extensions > Extensions installées`.

## Configuration
- Menu `UP Patterns > Configuration`.
- Définir les répertoires relatifs du thème à scanner :
  - Patterns (`patterns/` par défaut)
  - Templates (`templates/` par défaut)
  - Template parts (`parts/` par défaut)
- Ajuster les dossiers d’assets à rechercher (plusieurs lignes possibles) :
  - CSS / SCSS (`assets/css/block`, `assets/scss/block` par défaut)
  - JS (`assets/js/block` par défaut)
  - GSAP (`assets/js/gsap` par défaut)
  - Aperçus (`assets/images/block`, `assets/images`, `images` par défaut)

Les chemins sont relatifs au répertoire du thème actif. Chaque valeur peut être adaptée à votre structure.

## Importer un élément
1. Aller dans `UP Patterns` (page principale).
2. Les fichiers du thème sont triés par type : Patterns, Templates, Template Parts.
3. Cliquer sur **Importer dans le plugin** pour un élément disponible.
4. Le fichier est copié dans `ressources/{type}/{slug}/` avec ses assets.
5. Le `manifest.json` est mis à jour automatiquement.

## Structure du dossier `ressources/`
Pour un pattern nommé `hero-banner` :
```
ressources/
  patterns/
    hero-banner/
      pattern.php     (ou .html selon le thème)
      assets/
        css/
          hero-banner.css (si présent)
        js/
          hero-banner.js  (si présent)
        gsap/
          hero-banner.js  (si présent dans les dossiers GSAP)
        preview/
          hero-banner.jpg (s’il existe)
```
La même logique s’applique aux templates et template parts (`templates/` et `parts/`).

## Manifest JSON généré
Le fichier `manifest.json` est écrit à la racine du plugin. Sa structure est compatible avec l’onglet “Patterns (manifest)” du plugin `up-bulk-plugins-installer`.

Exemple de contenu après plusieurs imports :
```json
{
  "generated_at": "2025-10-17T17:40:12+00:00",
  "patterns": [
    {
      "slug": "hero-banner",
      "name": "Hero Banner",
      "type": "patterns",
      "files": {
        "pattern": "ressources/patterns/hero-banner/pattern.php",
        "style": "ressources/patterns/hero-banner/assets/css/hero-banner.css",
        "script": "ressources/patterns/hero-banner/assets/js/hero-banner.js",
        "gsap": "ressources/patterns/hero-banner/assets/gsap/hero-banner.js"
      },
      "install": {
        "pattern": "patterns",
        "style": "assets/css/block",
        "script": "assets/js/block",
        "gsap": "assets/js/gsap"
      },
      "preview": "ressources/patterns/hero-banner/assets/preview/hero-banner.jpg"
    }
  ],
  "templates": [
    {
      "slug": "page-contact",
      "name": "Page Contact",
      "type": "templates",
      "files": {
        "template": "ressources/templates/page-contact/page-contact.php"
      },
      "install": {
        "template": "templates"
      }
    }
  ],
  "parts": []
}
```

## Compatibilité avec up-bulk-plugins-installer
Le plugin `up-bulk-plugins-installer` attend une clé `patterns` contenant la liste des entrées à afficher. `up-patterns` fournit ce tableau ainsi que des sections `templates` et `parts` pour un usage interne ou futur. Lorsqu’un dépôt GitHub expose ce manifest, l’onglet “Patterns (manifest)” affichera automatiquement les entrées sous forme de cartes, avec aperçu et bouton d’installation.

Points clés pour la compatibilité :
- Chaque entrée `patterns` contient au minimum `slug`, `name`, `files.pattern` et `install.pattern`.
- Les assets optionnels (`style`, `script`, `gsap`, `preview`) sont ajoutés si présents.
- Les chemins sont relatifs à la racine du dépôt (ici au plugin). Lors d’une publication GitHub, conserver la même arborescence (`ressources/...`).

## Répertoires d’assets supportés
Par défaut, les fichiers sont recherchés sous :
- CSS/SCSS : `assets/css/block`, `assets/scss/block`
- JS : `assets/js/block`
- GSAP : `assets/js/gsap`
- Aperçus image : `assets/images/block`, `assets/images`, `images`

Vous pouvez ajouter ou supprimer des chemins via la page de configuration. Pour chaque pattern importé, le plugin tente de copier un fichier qui porte le même slug dans ces dossiers.

## Limitations
- L’import est unitaire (pas de bouton “Tout importer”).
- Aucune suppression automatique des entrées dans `ressources/` ni dans le manifest.
- Les sous-dossiers supplémentaires ne sont pas copiés (seulement les fichiers portant le slug correspondant).
- Les aperçus doivent partager le même slug (ex. `hero-banner.jpg`).

---

Auteur : GEHIN Nicolas
Version du plugin : 0.1.0
