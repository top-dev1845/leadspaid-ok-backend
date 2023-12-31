<?php

namespace App\Http\Controllers\Advertiser\Auth;

use Carbon\Carbon;
use App\UserLogin;
use App\Advertiser;
use App\Country;
use App\GeneralSetting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;


class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    // use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = 'advertiser/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
        $this->middleware('regStatus')->except('registrationNotAllowed');
        $this->activeTemplate = activeTemplate();
    }

    public function referralRegister($reference)
    {
        $page_title = "Sign Up";
        session()->put('reference', $reference);
        $info = json_decode(json_encode(getIpInfo()), true);
        $country_code = @implode(',', $info['code']);
        return view($this->activeTemplate . 'advertiser.auth.register', compact('reference', 'page_title','country_code'));
    }

    public function showRegistrationForm()
    {
        $page_title = "Advertiser Sign Up";
        $info = json_decode(json_encode(getIpInfo()), true);
        $country_code = @implode(',', $info['code']);
        $countries = Country::all();
        $type = 'Advertiser';
        return view($this->activeTemplate. 'register', compact('page_title','country_code', 'countries','type'));
    }


    public function register(Request $request)
    {

        $request->validate([
            'mobile' => 'required|string|unique:advertisers|min:6',
            'email' => 'required|string|email|max:160|unique:advertisers',
            'username' => 'required|unique:advertisers|min:6',
            'password' => 'required|string|min:6|confirmed',
        ]);
        if (isset($request->captcha)) {
            if (!captchaVerify($request->captcha, $request->captcha_secret)) {
                $notify[] = ['error', "Invalid Captcha"];
                return back()->withNotify($notify)->withInput();
            }
        }

        event(new Registered($user = $this->create($request->all())));

        $this->guard()->login($user);

        return $this->registered($request, $user)
            ?: redirect($this->redirectPath());
    }

    public function checkValidCode_adv($user, $code, $add_min = 10000)
    {
        if (!$code) return false;
        if (!$user->ver_code_send_at) return false;
        if ($user->ver_code_send_at->addMinutes($add_min) < Carbon::now()) return false;
        if ($user->ver_code !== $code) return false;
        return true;
    }

    public function varify_adv(Request $request){

        $data=$this->decode_arr($request->code_verifiyed);

       // $user = $this->guard()->user()->find($data['userid']);
         // $user = new Advertiser ();

         $user = Advertiser::findOrFail($data['userid']);
        // return $user->id;
        // if ($this->checkValidCode_adv($user, $user->ver_code, 2)) {
        //     $target_time = $user->ver_code_send_at->addMinutes(2)->timestamp;
        //     $delay = $target_time - time();
        //     $page_title = "user activate Error";
        //     $title='Please Try after ' . $delay . ' Seconds';
        //     $sub_title='';
        //    // return view($this->activeTemplate . 'email-verifyed', compact('page_title', 'title', 'sub_title'));
        //     //throw ValidationException::withMessages(['resend' => 'Please Try after ' . $delay . ' Seconds']);
        // }
        if (!$this->checkValidCode_adv($user, $user->ver_code)) {
            // First Time
            $page_title = "User Activation";
            $title='Thank you for verifying your account.';
            $sub_title='Your account is pending approval. <br> You will receive an email once it is activated.';
            $user->ver_code = $data['code'];
            $user->ver_code_send_at = Carbon::now();
            if($user->ev == 0){
                $user->ev = 1;
                $user->status = 0;
                $user->save();
            }
            send_email_adv_admin($user, 'EVER_CODE',$user->username);
            return view($this->activeTemplate . 'email-verifyed', compact('page_title', 'title', 'sub_title'));
        } else {
            $page_title = "User Already Activated";
            $title='Your email is already verified.';
            $sub_title='Your account is pending approval. <br>You will receive an email once it is activated.';
            // $user->ver_code = $user->ver_code;
            // $user->ver_code_send_at = Carbon::now();
            // $user->status = 0;
            // $user->save();
            return view($this->activeTemplate . 'email-verifyed', compact('page_title', 'title', 'sub_title'));
        }
    }

    public function encode_arr($data) {
        return base64_encode(serialize($data));
    }

    public  function decode_arr($data) {
        return unserialize(base64_decode($data));
    }

    public function resend_verification_code(Request $request){
        $user = Advertiser::findOrFail($request['id']);
        if(!$user){ return response()->json(['success'=>false]); }
        $code=[ 'code' =>verificationCode(6), 'userid'=>$user->id ];
        $useremail=$user->email;
        $urll= url('');
        $link=$urll.'/advertiser/register-veryfy/?code_verifiyed='.$this->encode_arr($code);
        // custom code email send
        send_email_adv($user, 'EVER_CODE',$link);
        return response()->json(['success'=>true]);
    }

    public function register_advertiser(Request $request){

        $request->validate([
            'email' => 'required|string|email|max:160|unique:advertisers',
            'password' => 'required|string|min:6|confirmed',
          ], [
            'email.required' => 'A email is required',
            'email.email' => 'Please specify a real email',
            'email.unique' => 'An Advertiser with email id ('.$request->email.') already exist.<br/> Please <a href ="https://leadspaid.com/login-advertiser"><u> Click here</u></a> to login or use a different email address to register.',
            'password.required' => 'Password is required.',
          ]);


        event(new Registered($user = $this->create_adv($request->all())));
        // $this->guard()->login($user);
        $code=[
            'code' =>verificationCode(6),
            'userid'=>$user->id
        ];
        $useremail=$user->email;
        $urll= url('');
        $link=$urll.'/advertiser/register-veryfy/?code_verifiyed='.$this->encode_arr($code);
        // custom code email send
        send_email_adv($user, 'EVER_CODE',$link);
        $page_title = "Thanks email";
        return view($this->activeTemplate . 'thanks-email', compact('page_title','useremail'));
    }

    protected function create_adv(array $data)
    {
        $gnl = GeneralSetting::first();
        $adv = new Advertiser ();
        $adv->name = $data['name'];
        $adv->email = $data['email'];
        $username=strstr($data['email'],'@',true);
        $adv->username = $username;
        $adv->country = $data['country'];
        $adv->company_name = $data['company_name'];
        $mobile = preg_replace('/\D/', '', $data['country_code'].$data['phone']);
        $adv->mobile = $mobile;
        $adv->product_services = $data['product_services'];
        $adv->Website = $data['Website'];
        $adv->Social = $data['Social'];
        $adv->ad_budget = $data['ad_budget'];
        $adv->country_code = $data['country_code'];
        $adv->password = Hash::make($data['password']);
        $adv->status = 0;
        $adv->ev = 0;
       // $adv->sv = 0;
        // $adv->ev = $gnl->ev==0 ? 1 : 0;
        $adv->sv = $gnl->sv==0 ? 1 : 0;
        $adv->ts = 0;
        $adv->tv = 0;
        $adv->save();
        $ip = $_SERVER["REMOTE_ADDR"];
        $exist = UserLogin::where('user_ip',$ip)->first();
        $userLogin = new UserLogin();
        if ($exist) {
            $userLogin->longitude =  $exist->longitude;
            $userLogin->latitude =  $exist->latitude;
            $userLogin->location =  $exist->location;
            $userLogin->country_code = $exist->country_code;
            $userLogin->country =  $exist->country;
        }else{
            $info = json_decode(json_encode(getIpInfo()), true);
            $userLogin->longitude =  @implode(',',$info['long']);
            $userLogin->latitude =  @implode(',',$info['lat']);
            $userLogin->location =  @implode(',',$info['city']) . (" - ". @implode(',',$info['area']) ."- ") . @implode(',',$info['country']) . (" - ". @implode(',',$info['code']) . " ");
            $userLogin->country_code = @implode(',',$info['code']);
            $userLogin->country =  @implode(',', $info['country']);
        }
        /*$userAgent = osBrowser();
        $userLogin->advertiser_id = $adv->id;
        $userLogin->user_ip =  $ip;
        $userLogin->browser = @$userAgent['browser'];
        $userLogin->os = @$userAgent['os_platform'];
        $userLogin->save(); */
        return $adv;
    }

    public function user_varify(Request $request)
    {

    }
    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        $gnl = GeneralSetting::first();
        $adv = new Advertiser ();
        $adv->name = $data['name'];
        $adv->email = $data['email'];
        $adv->username = $data['username'];
        $adv->country = $data['country'];
        $adv->city = $data['city'];
        $adv->company_name = $data['company_name'];
        $adv->billed_to = $data['billed_to'];
        $adv->postal_code = $data['postal_code'];
        $mobile = preg_replace('/\D/', '', $data['country_code'].$data['mobile']);
        $adv->mobile = $mobile;
        $adv->country_code = $data['country_code'];
        $adv->password = Hash::make($data['password']);
        $adv->status = 1;
        $adv->ev = $gnl->ev==0 ? 1 : 0;
        $adv->sv = $gnl->sv==0 ? 1 : 0;
        $adv->ts = 0;
        $adv->tv = 1;
        $adv->save();
        $ip = $_SERVER["REMOTE_ADDR"];
        $exist = UserLogin::where('user_ip',$ip)->first();
        $userLogin = new UserLogin();
        if ($exist) {
            $userLogin->longitude =  $exist->longitude;
            $userLogin->latitude =  $exist->latitude;
            $userLogin->location =  $exist->location;
            $userLogin->country_code = $exist->country_code;
            $userLogin->country =  $exist->country;
        }else{
            $info = json_decode(json_encode(getIpInfo()), true);
            $userLogin->longitude =  @implode(',',$info['long']);
            $userLogin->latitude =  @implode(',',$info['lat']);
            $userLogin->location =  @implode(',',$info['city']) . (" - ". @implode(',',$info['area']) ."- ") . @implode(',',$info['country']) . (" - ". @implode(',',$info['code']) . " ");
            $userLogin->country_code = @implode(',',$info['code']);
            $userLogin->country =  @implode(',', $info['country']);
        }
        $userAgent = osBrowser();
        $userLogin->advertiser_id = $adv->id;
        $userLogin->user_ip =  $ip;
        $userLogin->browser = @$userAgent['browser'];
        $userLogin->os = @$userAgent['os_platform'];
        $userLogin->save();
        return $adv;
    }

    public function registered()
    {
        return redirect()->route('advertiser.dashboard');
    }

    protected function guard()
    {
        return Auth::guard('advertiser');
    }
}
