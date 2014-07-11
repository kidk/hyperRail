<?php

use hyperRail\app\models\OAuthProviders\IOAuthProvider;

 /*
    |--------------------------------------------------------------------------
    | Twitter provider
    |--------------------------------------------------------------------------
 */

class TwitterProvider implements IOAuthProvider{

    /**
     * Login Twitter-service
     * 
     * @return mixed
     */
    public function getLogin() {
        $twitterService = OAuth::consumer("Twitter","irail.dev");

        // get data from input
        $code = Input::get('oauth_token');
        $verifier = Input::get('oauth_verifier');

        if (!empty($code)) {
            // This was a callback request from Twitter, get the token
            $token = $twitterService->requestAccessToken($code, $verifier);
            
            $access_token = $token->getAccessToken();
            $request_token = $token->getRequestTokenSecret();
            
            // Send a request with it
            $result = json_decode($twitterService->request('account/verify_credentials.json'));
           
            $credentials = array(
                'email' => $result->screen_name,
                'password' => $request_token
            );

           return $credentials;            
        }
        // if not ask for permission first
        else {

            // extra request needed for oauth1 to request a request token
            $token = $twitterService->requestRequestToken();

            // get twitterService authorization
            $url = $twitterService->getAuthorizationUri(array('oauth_token' => $token));

            header('Location: ' . (string) $url);
            die();
        }
    }

    /**
     * Save Twitter-user in userstable
     * 
     * @return void
     */
    private function saveUser($result, $request_token, $access_token){
        $twitteruser = DB::table('users')->where('email', $result->screen_name)->first();

        if (empty($twitteruser)) {
            $data = new User;
            
            $data->provider = 'twitter';
            $data->token = $access_token;
            $data->email = $result->screen_name;
            $data->first_name = $result->name;
            $data->password = Hash::make($request_token);
            $data->activated = 1;
       
            $data->save();
        }
    }

    /**
     * Get Twitter-friends of the current logged in user
     * 
     * @return mixed
     */
    public function getFriends() {    
        if ( Sentry::check()){
            // get ID of current logged in user
            $user_id = Auth::user()->id;
            dd($user_id);

            // check if user is logged in with Twitter
            $results = DB::select('select * from users where id = ? and provider = "twitter"', array($user_id));

            dd($results);

            $twitterService = OAuth::consumer("Twitter","irail.dev/oauth/twitter");

            $result2 = json_decode($twitterService->request('friends/ids.json?cursor=-1&screen_name=brechtvdv&count=5000'));
            //$result2 = json_decode($twitterService->request('friends/list.json?cursor=-1&screen_name=brechtvdv&skip_status=true&include_user_entities=false'));
              
            dd($result2);   
        }  
    } 

} 

?>