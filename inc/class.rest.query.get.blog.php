<?php
/*
*Methode permettant de retourner des informations sur un blog particulier
*/
class RestQueryGetBlog extends RestQuery
{
  public function __construct(){
    global $core;
  }
  
  /*
  * ça se complique niveau droits
  *
  * SI L'utilisateur n'est pas authentifié
  *    le blog est hors ligne
  *       -> 404
  *    l'API n'est pas publique
  *      -> refus
  *   l'API est publique
  *     -> OK, mais on ne retourne pas les infos techniques
  * L'utilisateur est authentifié
  *   n'est pas admin (du blog en question)
  *     -> OK, mais on ne retourne pas les infos techniques
  *   est admin
  *     -> L'API retourne le maximum d'infos
  */
}