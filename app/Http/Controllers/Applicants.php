<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use Mailgun\Mailgun;
use Mail;
use League\Csv\Writer;
use League\Csv\Reader;
use Auth;

use App\User;
use App\Models\Blueprint;
use App\Models\Applicant;

class Applicants extends Controller
{
  // 
  // [ OPTIONS FOR SUBMIT FORMS ]
  //
  //
  public function index(){
    $user = Auth::user();
    $blueprints = $user->level == 3 ? Blueprint::with("applicants")->get() : $user->blueprints->with("applicants")->get();
   
    $data['title']       = 'Encuestas por aplicar Tú Evalúas';
    $data['description'] = '';
    $data['body_class']  = 'cuestionarios';
    $data['user']        = $user;
    $data['blueprints']  = $blueprints;

    return view("applicants")->with($data);
  }

  //
  // [ SEND ONE INVITATION ]
  //
  //
  public function mailto(Request $request){
    $user      = Auth::user();
    $creator   = $user->id;
    $blueprint = Blueprint::find($request->input('id'));
    $email     = $request->input("email");
    $form_key  = md5('blueprint' . $blueprint->id . $email);

    $applicant = Applicant::firstOrCreate([
      "blueprint_id" => $blueprint->id, 
      "form_key"     => $form_key, 
      "user_email"   => $email
      ]);
    $this->sendForm($applicant);
    return redirect('dashboard/encuestados');
  }

  //
  // [ SEND MAIL WITH MAILGUN ]
  //
  //
  public function sendForm($applicant){
    Mail::send('email.invitation', ['applicant' => $applicant], function ($m) use ($applicant) {
            $m->from('howdy@tuevaluas.com.mx', 'Howdy friend');

            $m->to($applicant->user_email, "amigo")->subject('Invitación a opinar!');
    });
    /*
    $from    = "howdy@tuevaluas.com.mx";
    $subject = "Invitación a opinar";
    $to      = $applicant->user_email;
    $mailgun = new Mailgun (env('MAILGUN_KEY'));

    $message = [
      'from'    => $from,
      'to'      => $to,
      'subject' => $subject,
      'html'    => view('email.invitation')->with(["applicant" => $applicant])
    ];
    
    $mailgun->sendMessage(env('MAILGUN_DOMAIN'), $message);
    */
  }

  //
  // [ OPTIMIZE RAM WITH A GENERATOR ]
  //
  //
  private function makeRange($length) {
    for ($i = 0; $i < $length; $i++) {
        yield $i;
    }
  }
}