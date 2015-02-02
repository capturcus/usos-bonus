<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

/**
* Hybrid_Providers_Usosweb provider adapter based on OAuth1 protocol
* Adapter to Usosweb API by Henryk Michalewski
*/

function workDatTree($node, $parent, &$treeResult) {
	if(!isset($node))
		return;

	$treeResult[$node->node_id] = new NodeContent();
	$treeResult[$node->node_id]->name = $node->name->pl;
	$treeResult[$node->node_id]->type = $node->type;
	$treeResult[$node->node_id]->pkt = 0;
	$treeResult[$node->node_id]->parent = ($parent == NULL ? NULL : $parent->node_id);

	if(!isset($node->subnodes))
		return;
	foreach ($node->subnodes as $n) {
		workDatTree($n, $node, $treeResult);
	}
}

class NodeContent {
	public $name;
	public $type;
	public $pkt;
	public $comment;
	public $parent;
}

class Hybrid_Providers_Usosweb extends Hybrid_Provider_Model_OAuth1
{
	/**
	* IDp wrappers initializer 
	*/
	/* Required scopes. The only functionality of this application is to say hello,
    * so it does not really require any. But, if you want, you may access user's
    * email, just do the following:
    * - put array('email') here,
    * - append 'email' to the 'fields' argument of 'services/users/user' method,
    *   you will find it below in this script.
    */
	
	function initialize()
	{
		parent::initialize();

		// Provider api end-points 
		$this->api->api_base_url      = "https://usosapps.uw.edu.pl/";
		$this->api->request_token_url = "https://usosapps.uw.edu.pl/services/oauth/request_token?scopes=studies|crstests";
		$this->api->access_token_url  = "https://usosapps.uw.edu.pl/services/oauth/access_token";
		$this->api->authorize_url = "https://usosapps.uw.edu.pl/services/oauth/authorize";

	}
    /**
	* begin login step 
	*/
	function loginBegin()
	{
		$tokens = $this->api->requestToken( $this->endpoint ); 

		// request tokens as received from provider
		$this->request_tokens_raw = $tokens;
		
		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 ){
			throw new Exception( "Authentication failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ), 5 );
		}

		if ( ! isset( $tokens["oauth_token"] ) ){
			throw new Exception( "Authentication failed! {$this->providerId} returned an invalid oauth_token.", 5 );
		}

		$this->token( "request_token"       , $tokens["oauth_token"] ); 
		$this->token( "request_token_secret", $tokens["oauth_token_secret"] ); 

		# redirect the user to the provider authentication url
		Hybrid_Auth::redirect( $this->api->authorizeUrl( $tokens ) );
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
        global $wpdb;

		$response = $this->api->get('https://usosapps.uw.edu.pl/services/crstests/participant');

		$currentTerm = "";

		foreach ($response->terms as $key => $value) {
			$d = date_create_from_format("Y-m-d", $value->finish_date);
			$interval = $d->diff(new DateTime());
			if($interval->invert == 1){
				$currentTerm = $key;
			}
		}

		$roots = array();

		foreach ($response->tests as $semesters) {
			foreach ($semesters as $key => $value) {
				$roots[$key] = $value->course_edition;
			}
		}

		foreach ($roots as $key => $value) {
			$wpdb->get_results("insert into przedmioty (przedmioty_id, nazwa, ID) values (\"{$value->course_id}\",\"{$value->course_name->en}\", NULL);");
		}

		$bigTreeResult = array();

		foreach ($roots as $key => $value) {
			$request = "https://usosapps.uw.edu.pl/services/crstests/node?node_id={$key}&recursive=true&fields=node_id|name|subnodes|type";
			$response = $this->api->get($request);
			$treeResult = array();
			workDatTree($response, NULL, $treeResult);
			$bigTreeResult[$value->course_id] = $treeResult;
			$bigTreeResult[$value->course_id][0] = ($value->term_id == $currentTerm ? 1 : 0);
		}

		foreach ($bigTreeResult as $courseId => $arrayOfNodeContents) {
			$pktIds = array();
			$isCurrent = $arrayOfNodeContents[0];
			foreach ($arrayOfNodeContents as $nodeId => $content) {
				if($nodeId == 0)
					continue;
				if ($content->type == "pkt") {
					$pktIds[] = $nodeId;
				}
				if($content->type == "root"){
					$content->type .= ("|" . $isCurrent);
				}
			}
			$request = "https://usosapps.uw.edu.pl/services/crstests/user_points?node_ids=".implode("|", $pktIds);
			$response = $this->api->get($request);
			foreach ($response as $testPoints) {
				$bigTreeResult[$courseId][$testPoints->node_id]->pkt = $testPoints->points;
				$bigTreeResult[$courseId][$testPoints->node_id]->comment = $testPoints->comment;
			}
		}

		$response = $this->api->get( 'https://usosapps.uw.edu.pl/services/users/user?fields=id|first_name|last_name|sex|homepage_url|profile_url' );

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ), 6 );
		}

		if ( ! is_object( $response ) || ! isset( $response->id ) ){
			throw new Exception( "User profile request failed! {$this->providerId} api returned an invalid response.", 6 );
		}

		# store the user profile. 
		# written without a deeper study what is really going on in Usosweb API
		 
		$this->user->profile->identifier  =	(property_exists($response,'id'))?$response->id:"";
		$this->user->profile->displayName =	(property_exists($response,'first_name') && property_exists($response,'last_name'))?$response->first_name." ".$response->last_name:"";
		$this->user->profile->lastName   =	(property_exists($response,'last_name'))?$response->last_name:""; 
		$this->user->profile->firstName   =	(property_exists($response,'first_name'))?$response->first_name:""; 
        $this->user->profile->gender =		(property_exists($response,'sex'))?$response->sex:""; 
		$this->user->profile->profileURL  =	(property_exists($response,'profile_url'))?$response->profile_url:"";
		$this->user->profile->webSiteURL  =	(property_exists($response,'homepage_url'))?$response->homepage_url:""; 

		$result = $wpdb->get_results("delete from point_nodes where identifier = {$this->user->profile->identifier};");

		foreach ($bigTreeResult as $courseId => $arrayOfNodeContents) {
			foreach ($arrayOfNodeContents as $nodeId => $content) {
				if($nodeId == 0)
					continue;
				$content->pkt = isset($content->pkt) ? $content->pkt : 0;
				$content->parent = isset($content->parent) ? $content->parent : 0;
				$wpdb->get_results("insert into point_nodes values ($nodeId, \"{$content->type}\", \"{$content->name}\", {$content->pkt}, \"{$content->comment}\", {$content->parent}, {$this->user->profile->identifier}, \"{$courseId}\");");
			}
		}

		$response = $this->api->get( 'https://usosapps.uw.edu.pl/services/tt/student?fields=start_time|end_time|course_id|course_name|classtype_name' ); //&start=2015-01-20

		$result = $wpdb->get_results("delete from zajecia where identifier = {$this->user->profile->identifier};");

        foreach ($response as $value) {
        	$result = $wpdb->get_results
        	(
        		"insert into przedmioty (przedmioty_id, nazwa, ID) values (\"{$value->course_id}\",\"{$value->course_name->en}\", NULL);"
        	);
        	$result = $wpdb->get_results(
        		"insert into zajecia (przedmioty_id, start_time, end_time, identifier, typ) values
        		(\"{$value->course_id}\", \"{$value->start_time}\", \"{$value->end_time}\", {$this->user->profile->identifier}, \"{$value->classtype_name->en}\");"
        	);
    	}

		return $this->user->profile;
 	}

}