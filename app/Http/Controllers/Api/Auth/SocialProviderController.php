<?php

namespace App\Http\Controllers\api\auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

class SocialProviderController extends Controller
{
    use ApiResponser;
    
    public function loginCallback()
    {
        if(!isset($_REQUEST['social_provider_name']) || !$_REQUEST['social_provider_name']){
            return $this->error('social provider not provided', 400);
        }
        $socialProviderName = strtolower($_REQUEST['social_provider_name']);
        if(!in_array($socialProviderName, ['google'])){
            return $this->error('social provider not supported', 400);
        }
        $socialProviderUser = null;
        $user = null;
        try {
            $socialProviderUser = Socialite::driver($socialProviderName)->user();
        } catch (\Throwable $th) {
            return $this->error('not user info provided to server', 400, $socialProviderUser);
        }

        DB::transaction(function () use ($socialProviderUser, &$user, $socialProviderName) {
            $socialAccount = SocialAccount::firstOrNew(
                ['social_id' => $socialProviderUser->getId(), 'social_provider' => $socialProviderName],
                ['social_name' => $socialProviderUser->getName()]
            );

            if (!($user = $socialAccount->user)) {
                $user = User::create([
                    'email' => $socialProviderUser->getEmail(),
                    'name' => $socialProviderUser->getName(),
                ]);
                $socialAccount->fill(['user_id' => $user->id])->save();
            }
        });

        return $this->success([
            'user' => $user,
            'social_provider_user' => $socialProviderUser,
        ]);
    }
    public function loginUrl()
    {
        $socialProvidersNames = ['google', 'google'];
        $urls = [];
        foreach ($socialProvidersNames as $socialProviderName) {
            array_push($urls, [$socialProviderName => Socialite::driver($socialProviderName)->redirect()->getTargetUrl()]);
        }
        return $this->success([
            'urls' => $urls,
        ]);
    }
}
