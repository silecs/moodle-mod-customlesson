# Leçons personnalisées, guide de l'utilisateur

## Fonctionnement général

L'activité est une version étendue du module "Lesson" livré dans Moodle.
Toutes ses fonctionnalités ont été conservées.

## Personnaliser les lessons

La personnalisation se réalise en 2 phases :

* création du canevas de contenu,
* importation des données individuelles.

### Création du canevas de contenu

Il s'agit de créer une leçon dans laquelle certains éléments seront
ensuite remplacés par des données individuelles.
Les éléments remplacés peuvent se trouver dans tous les composants des questions,
à savoir :

* le champ **Contenu de la page**,
* les champs **Réponse**,
* les champs **Feedback**.

La syntaxe des éléments à remplacer est `[nom_du_paramètre]`.
Il est recommandé d'utiliser des préfixes pour clarifier les paramètres.
Par exemple, le contenu d'une question pourrait être :

    Quelle est la complexité de l'algorithme [q:algo] ?

### Importation des données individuelles

Sur la page de modification d'une leçon, un nouveau lien a été ajouté :
"Importer les données individuelles".

![Import](images/import-arrow.png)

Ce lien amène à une page de dépôt du fichier CSV.
Une fois le formulaire soumis, une page d'information affiche le résultat de l'opération.
L'opération n'est effective que si le fichier est valide.

### Format du fichier CSV

* La première ligne est formée des entêtes de colonnes.
* L'entête doit contenir au moins une des colonnes **userid** ou **username**.
* Les autres entêtes sont des noms de paramètres à substituer.
* L'ordre des colonnes d'entête est sans importance.
* Le contenu d'une cellule peut être entouré de guillemets "".

Par exemple, pour remplacer les expressions `[q:algo]` et `[r:complexity]`
pour deux utilisateurs de logins "jean" et "louise" :

    username;q:algo;r:complexity
    jean;quicksort;n^2
    louise;tri fusion;n log n

Les autres utilisateurs garderont l'affichage des paramètres non-substitués.
