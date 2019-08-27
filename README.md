# Aries

Un Framework PHP permetant d'aider les développeurs

## Commencement

Installer Git, executer `git clone https://github.com/MeDrioX/Aries.git`

Configurer votre base de données dans le fichier `config/config.php`

## Prérequis 

Utilisez les dernières versions de PHP


## Utilisation du système de route

Rien de plus simple. Un fichier web se trouvant dans le dossier routes

Dès que vous êtes dans le fichier juste à faire ceci : `$router->add('methodes', 'lien', 'controller@fonction', 'nom');`

Exemple :
```
$router->add('POST', 'login', 'LoginController@login', 'login');
```


## Fonctionnement des URLs

Les urls fonctionne avec un système de regex simplifié

Par exemple pour accepter que des chiffres dans votre url vous avez juste à faire `$router->add('GET', 'article[i:id]', 'ArticleController@article', 'article');`

Listes des regex pré-enregistré:

```
'i'  => '[0-9]++'
'a'  => '[0-9A-Za-z]++'
'h'  => '[0-9A-Fa-f]++'
'*'  => '.+?'
'**' => '.++'
''   => '[^/\.]++'
```

## Moteur de template Pyxis

Le moteur de tempalte n'est pas encore complet vous pouvez juste include un fichier pyxis dans un autre

Exemple 
```
<?php \Core\Pyxis\Pyxis::include('nom_de_la_vue'); ?>
```

## Système d'ORM

Un système d'ORM pas encore complet mais fonctionnel !

Pour l'utiliser créer un fichier dans `app/Model/Nom.php`

Le fichier doit ressembler à ceci


```
namespace App\Model;

use Core\Sagitta\DataModel;

class User extends DataModel {

    protected static $tableName = 'nom de la table';

}
```

Pour l'utiliser vous aurez juste à faire dans un controller par exemple :

```
User::createConnection();
$user = new User();
$user->set('field_name', 'value');
$user->set('field_name', 'value');
$user->set('field_name', 'value');
$user->set('field_name', 'value');
$user->save();
```

## Fin

Si vous rencontrer des soucis n'hésitez pas à me contacter et rejoindre le Discord de Aries : https://discord.gg/KkY6bfa
