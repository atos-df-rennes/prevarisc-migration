/*
 * Point d'entrée AssetMapper pour les futurs modules ES natifs (nouveau code
 * first-party uniquement). Le JS/CSS legacy (jQuery et plugins associés) est
 * volontairement chargé en <script>/<link> classiques via asset() dans
 * base.html.twig : ces scripts s'appuient partout dans le code sur un ordre
 * d'exécution synchrone et des variables globales (window.$/jQuery), ce
 * qu'un module ES (toujours différé) ne garantit pas.
 */
