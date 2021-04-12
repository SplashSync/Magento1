---
lang: fr
permalink: start/configure
title: Configuration du Module
---

### Activez le Module

Le module Splash est automatiquement activé lorsque vous copiez des fichiers sur Magento.

Si ce n'est pas le cas, suivez ces deux étapes:
* Effacez le cache de Magento dans **System >> Gestion du cache >> Configuration**

![]({{ "/assets/img/screenshot_10.png"|relative_url}})

* Vérifiez que le fichier de déclaration du module est présent sur votre serveur

![]({{ "/assets/img/screenshot_1.png"|relative_url}})

Si le message "404 Error" s'affiche lorsque vous accédez à la page de configuration du module, vous devez vous **Déconnecter & Reconnecter** afin de mettre à jour la configuration vos droits (ACL).

### Configurer le Module

La configuration du module est accéssible dans **System >> Configuration** then **Services >> Splash Sync Connector**.

### Connecter votre compte Splash

D'abord, vous devez créer des clés d'accès pour votre module sur notre site. Pour ce faire, sur votre compte Splash, allez sur ** Serveurs ** >> ** Ajoutez un serveur ** et notez vos clés d'identification et de cryptage qui vous seront données.

![]({{ "/assets/img/screenshot_2.png"|relative_url}})

Ensuite, entrez les clés de la configuration du module (attention à ne pas oublier de caractère).

![]({{ "/assets/img/screenshot_3.png"|relative_url}})

##### Langue par défaut

Sélectionnez la langue par défaut à utiliser pour la communication avec les serveurs de Splash.

### Configurer les paramètres par défaut

Pour fonctionner correctement, le module a besoin de quelques paramètres.

##### Utilisateur par défaut

Entrez le Login et Mot de passe de l'utilisateur qui sera utilisé pour toutes les actions exécutées par le Module Splash.

![]({{ "/assets/img/screenshot_4.png"|relative_url}})

Nous recommandons fortement la création d'un utilisateur **dédié** pour Splash.

Soyez conscient que le module Splash prends en compte la configuration des droits des utilisateurs, cet utilisateur doit donc disposer des droit appropriés pour interagir avec Magento.

##### Traduction des données

Avec Splash, il est possible de synchroniser des champs multilingues. Cette fonction est principalement utilisée pour la synchronisation du catalogue de produits.

Si votre site n'utilise qu'une seule langue, laissez ce paramètre sur **Non** et sélectionnez votre langue.

![]({{ "/assets/img/screenshot_5.png"|relative_url}})

Si vous disposez d'un site multilingue, sélectionnez **Oui** et une option sera affichée sur chaque vue pour sélectionner la langue associée.

![]({{ "/assets/img/screenshot_6.png"|relative_url}})

##### Synchronisation des Clients

Si vous décidez d'importer des clients d'un autre site, vous devez définir ici le Website que Splash devra utiliser sur Magento pour créer les nouveaux clients.

![]({{ "/assets/img/screenshot_7.png"|relative_url}})

Si vous avez plusieurs sites, il est également possible de rediriger les nouveaux clients vers plusieurs WebSites, ce choix se fait sur la configuration de chaque site.

Le numéro du serveur est celui présent dans la colonne **#** de votre liste de serveurs.

##### Synchronisation des Produits

Si vous décidez d'importer des produits depuis d'autres sites, vous devez sélectionner leurs paramètres par défaut.

* Default Attribute set
* Default Warehouse

![]({{ "/assets/img/screenshot_8.png"|relative_url}})

### Vérifiez les résultats des Self-Tests

Une fois que votre module est prêt, ou chaque fois que vous mettez à jour vos paramètres, vous devez vérifier votre configuration.

Pour ce faire, allez sur la page du module: **System >> Web Services >> SOAP - Splash Sync**

Chaque fois que vous mettez à jour votre configuration, le module vérifiera vos paramètres et vous assurera que la communication avec Splash fonctionne bien.

Assurez-vous que tous les tests sont passés... c'est critique! Vérifiez également le reste de votre configuration, principalement le mappage des langues et des sites Web.

**Note** Si votre serveur n'était pas encore connecté, cela se fera lors du chargement de cette page.

![]({{ "/assets/img/screenshot_9.png"|relative_url}})
