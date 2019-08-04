<?php namespace Auth\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Auth\Auth\Entities\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Lang;
use Tymon\JWTAuth\Facades\JWTAuth;
use Laravel\Socialite\Facades\Socialite;

use Exception;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     * @throws ValidationException
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            $this->sendLockoutResponse($request);
        }

        // Get a JWT token via given credentials.
        try {
            $credentials = $this->credentials($request);
            if ($token = JWTAuth::attempt($credentials)) {
                // return token
                return $this->respondWithToken($token);
            }
        } catch (JWTException $e) {
            // Increments attempts
            $this->incrementLoginAttempts($request);
            // something went wrong whilst attempting to encode the token
            return $this->responseError(Lang::get('auth.failed'), 422);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);
        return $this->responseError(Lang::get('auth.failed'), 422);
    }

    /**
     * Get the token array structure.
     *
     * @param $token
     * @return JsonResponse
     */
    private function respondWithToken($token) : \Illuminate\Http\JsonResponse
    {
        $response = [
            'token' => $token,
            'token_type' => 'bearer',
        ];

        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Get the authenticated User
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(
            fractal()
                ->item(auth()->guard('api')->user())
                ->transformWith(function ($el) {
                    return [
                        'name' => $el->name,
                        'email' => $el->email,
                        'images' => $el->providerSocialites->where('image', '<>', null)->pluck('image')->all()
                    ];
                })
                ->toArray(),
            Response::HTTP_OK
        );
    }

    /**
     * Log the user out (Invalidate the token)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->guard('api')->logout(true);
        return response()->json($this->prepareResponse(['message' => trans('auth.success_logout')]));
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        $token = auth()->guard('api')->refresh(true, true);
        auth()->guard('api')->setToken($token);

        return $this->respondWithToken($token);
    }

    /**
     * Login com rede social
     *
     * @param $provider
     * @return mixed
     */
    public function redirect($provider)
    {
        return Socialite::driver($provider)->stateless()->redirect();
    }

    /**
     * Callback pra login com rede social
     *
     * @param $provider
     * @return array|JsonResponse
     */
    public function callback($provider){
        $userSocial = Socialite::driver($provider)->stateless()->user();
        $user = User::where(['email' => $userSocial->getEmail()])->first();

        if (!$user) {
            $user = User::create([
                'name' => $userSocial->getName(),
                'email' => $userSocial->getEmail(),
                'password' => bcrypt(uniqid('secret_'))
            ]);

            if (!$user) {
                return $this->responseError(Lang::get('auth.failed'), 422);
            }
        }

        $provider = $user->providerSocialites
            ->where('provider', '=', $provider)
            ->where('provider_id', '=', $userSocial->getId())
            ->first();

        if (!$provider) {
            $user->providerSocialites()
                ->create([
                    'image' => $userSocial->getAvatar(),
                    'provider_id' => $userSocial->getId(),
                    'provider' => $provider
                ]);
        }

        return $this->respondWithToken(auth()->guard('api')->login($user));
    }
}
