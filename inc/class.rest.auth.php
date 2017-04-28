<?php

class restAuth extends dcAuth
{
	# L'utilisateur n'a pas le droit de changer son mot de passe
	protected $allow_pass_change = false;
 
	# La méthode de vérification du mot de passe
	public function checkUser($api_key)
	{

 
		# Si un mot de passe a été donné, nous allons le vérifier avec la
		# méthode auth.login XML-RPC.


			# Les opérations précédentes se sont déroulées sans erreur, nous
			# pouvons maintenant appeler la méthode parente afin d'initialiser
			# l'utilisateur dans l'object $core->auth
			return parent::checkUser($user_id,$pwd);
		

	}
}