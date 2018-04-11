
## Configuration

### Activez le Module 

Le module Splash est automatiquement activé lorsque vous copiez des fichiers sur Magento.

Si ce n'est pas le cas, suivez ces deux étapes:
* Effacez le cache de Magento dans **System >> Gestion du cache >> Configuration**

<p align="center">
    <img src="https://raw.githubusercontent.com/wiki/SplashSync/Magento1/Img/screenshot_10.png">
</p>

* Vérifiez que le fichier de déclaration du module est présent sur votre serveur

<p align="center">
    <img src="https://raw.githubusercontent.com/wiki/SplashSync/Magento1/Img/screenshot_1.png">
</p>

Si le message "404 Error" s'affiche lorsque vous accédez à la page de configuration du module, vous devez vous **Déconnecter & Reconnecter** afin de mettre à jour la configuration vos droits (ACL).

### Configurer le Module

La configuration du module est accéssible dans **System >> Configuration** then **Services >> Splash Sync Connector**. 

### Connecter votre compte Splash

D'abord, vous devez créer des clés d'accès pour votre module sur notre site. Pour ce faire, sur votre compte Splash, allez sur ** Serveurs ** >> ** Ajoutez un serveur ** et notez vos clés d'identification et de cryptage qui vous seront données.

![](https://raw.githubusercontent.com/wiki/SplashSync/Magento1/Img/screenshot_2.png)

Ensuite, entrez les clés de la configuration du module (attention à ne pas oublier de caractère).

![](https://raw.githubusercontent.com/wiki/SplashSync/Magento1/Img/screenshot_3.png)

##### Langue par défaut

Sélectionnez la langue par défaut à utiliser pour la communication avec les serveurs de Splash.

### Configurer les paramètres par défaut

Pour fonctionner correctement, le module a besoin de quelques paramètres. 

##### Utilisateur par défaut

Entrez le Login et Mot de passe de l'utilisateur qui sera utilisé pour toutes les actions exécutées par le Module Splash.

![](https://raw.githubusercontent.com/wiki/SplashSync/Magento1/Img/screenshot_4.png)

Nous recommandons fortement la création d'un utilisateur **dédié** pour Splash.

Soyez conscient que le module Splash prends en compte la configuration des droits des utilisateurs, cet utilisateur doit donc disposer des droit appropriés pour interagir avec Magento.

##### Traduction des données

Avec Splash, il est possible de synchroniser des champs multilingues. Cette fonction est principalement utilisée pour la synchronisation du catalogue de produits.

Si votre site n'utilise qu'une seule langue, laissez ce paramètre sur **Non** et sélectionnez votre langue. 

![](https://raw.githubusercontent.com/wiki/SplashSync/Magento1/Img/screenshot_5.png)

Si vous disposez d'un site multilingue, sélectionnez **Oui** et une option sera affichée sur chaque vue pour sélectionner la langue associée.

![](https://raw.githubusercontent.com/wiki/SplashSync/Magento1/Img/screenshot_6.png)

##### Synchronisation des Clients

Si vous décidez d'importer des clients d'un autre site, vous devez définir ici le Website que Splash devra utiliser sur Magento pour créer les nouveaux clients. 

![](https://raw.githubusercontent.com/wiki/SplashSync/Magento1/Img/screenshot_7.png)

Si vous avez plusieurs sites, il est également possible de rediriger les nouveau clienst vers plusieurs WebSites, ce choix se fait sur la configuration de chaque site. 

Le numéro du serveur est celui présent dans la colonne **#** de votre liste de serveurs.

##### Synchronisation des Produits

Si vous décidez d'importer des produits depuis d'autres sites, vous devez sélectionner leurs paramètres par défaut. 

* Default Attribute set
* Default Warehouse

![](https://raw.githubusercontent.com/wiki/SplashSync/Magento1/Img/screenshot_8.png)

### Vérifiez les résultats des Self-Tests

Une fois que votre module est prêt, ou chaque fois que vous mettez à jour vos paramètres, vous devez vérifier votre configuration.

Pour ce faire, allez sur la page du module: **System >> Web Services >> SOAP - Splash Sync**

Chaque fois que vous mettez à jour votre configuration, le module vérifiera vos paramètres et vous assurera que la communication avec Splash fonctionne bien.

Assurez-vous que tous les tests sont passés... c'est critique! Vérifiez également le reste de votre configuration, principalement le mappage des langues et des sites Web.

**Note** Si votre serveur n'était pas encore connecté, cela se fera lors du chargement de cette page.

![](https://raw.githubusercontent.com/wiki/SplashSync/Magento1/Img/screenshot_9.png)