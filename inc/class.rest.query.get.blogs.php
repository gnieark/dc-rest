<?php

class RestQueryGetBlogs extends RestQuery
{
	public function __construct($user){
		if($user === false){
			//need To be authentified
			$this->response_code = 403;
			$this->response_message = array('code' => 403, 'error' => 'get Blogs methods requires to be authentified');
			return;
		}
		//error_log(json_encode($user->findUserBlog()));
		
		
	}


}