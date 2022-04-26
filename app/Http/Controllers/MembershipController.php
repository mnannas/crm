<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use App\Product_Features;
use App\Membership;
use App\Membership_Level;
use App\Student;
use App\Payment;
use App\Ticket;
use App\Product;
use App\Imports\MembershipImport;
use App\Exports\MembersFormat;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Mail;
use Auth;
use DB;
use App\Invoice;
use PDF;

class MembershipController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function view_membership()
    {
        $membership = Membership::orderBy('id','desc')->paginate(15);
        
        return view('admin.membership.membership', compact('membership'));
    }

    public function store_membership(Request $request)
    {
        $membership = Membership::orderBy('id','desc')->first();
        $membership_level = Membership_Level::orderBy('id','desc')->first();

        $auto_inc_mb = $membership->id + 1;
        $membership_id = 'MB' . 0 . 0 . $auto_inc_mb;
        
        Membership::create(array(

            'membership_id'=> $membership_id,
            'name' => $request->name

        ));  

        foreach($request->level as $keys => $values) {

            $level_id = 'MBL' . uniqid();
                    
            Membership_Level::create(array(
                'level_id'=> $level_id,
                'name'=> $values,
                'membership_id'=> $membership_id
            ));
        }

        return redirect('membership')->with('success', 'Membership Successfully Created'); 
    }

    public function update_membership_level(Request $request,$lvl_id)
    {
        $membership_level = Membership_Level::where('level_id', $lvl_id)->first();

        $membership_level->description = $request->description;
        $membership_level->price = $request->price;
        $membership_level->tax = $request->tax;
        
        $membership_level->save();

        return redirect()->back()->with('success', 'Price Updated');
    }

    public function view_level($membership_id)
    {
        $membership = Membership::where('membership_id', $membership_id)->first();
        $membership_level = Membership_Level::where('membership_id', $membership_id)->paginate(15);

        $total = Student::where('membership_id', $membership_id)->count();
        $totalactive = Student::where('status','Active')->where('membership_id', $membership_id)->count();
        $totaldeactive = Student::where('status','Deactive')->where('membership_id', $membership_id)->count();
        $totalbreak = Student::where('status','Break')->where('membership_id', $membership_id)->count();
        $totalstop = Student::where('status','Stop')->where('membership_id', $membership_id)->count();
        $totalpending = Student::where('status','Pending')->where('membership_id', $membership_id)->count();
        $totalendmembership = Student::where('status','End-Membership')->where('membership_id', $membership_id)->count();
        $totalupgradepro = Student::where('status','Upgrade-Pro')->where('membership_id', $membership_id)->count();
        $totalterminate = Student::where('status','Terminate')->where('membership_id', $membership_id)->count();
                
        return view('admin.membership.level', compact('membership', 'membership_level', 'total', 'totalactive', 'totaldeactive', 'totalbreak', 'totalstop', 'totalpending', 'totalendmembership', 'totalupgradepro', 'totalterminate'));
    }

    public function export_members($membership_id)
    {
        $student = Student::where('membership_id', $membership_id)->get();
        $membership = Membership::where('membership_id', $membership_id)->first();
        $level = Membership_Level::where('membership_id', $membership_id)->get();

        // return Excel::download(new ProgramExport($payment, $student, $package), $product->name.'.xlsx');
        /*-- Manage Email ---------------------------------------------------*/
        $fileName = $membership->name.'.csv';
        $columnNames = [
            'Customer ID',
            'First Name',
            'Last Name',
            'IC No',
            'Phone No',
            'Email',
            'Membership',
            'Status',
            'Registered At'
        ];
        
        $file = fopen(public_path('export/') . $fileName, 'w');
        fputcsv($file, $columnNames);
        
        foreach ($student as $students) {
            foreach($level as $levels){
                if($membership->membership_id == $students->membership_id){
                    if($levels->level_id == $students->level_id){

                        fputcsv($file, [
                            $students->stud_id,
                            $students->first_name,
                            $students->last_name,
                            $students->ic,
                            $students->phoneno,
                            $students->email,
                            $levels->name,
                            $students->status,
                            $students->created_at,
                        ]);

                    }
                }
            }
            
        }
        
        fclose($file);

        
        Mail::send('emails.export_mail', [], function($message) use ($fileName)
        {
            $message->to(Auth::user()->email)->subject('ATTACHMENT OF MEMBERSHIP DETAILS');
            $message->attach(public_path('export/') . $fileName);
        });

        return redirect('membership/level/'.$membership_id)->with('export-members','The data will be sent to your email. It may take a few minutes to successfully received.');

    }

    public function view($membership_id, $level_id)
    {
        $student = Student::where('membership_id', $membership_id)->where('level_id', $level_id)->paginate(15);
        $membership = Membership::where('membership_id', $membership_id)->first();
        $membership_level = Membership_Level::where('membership_id', $membership_id)->where('level_id', $level_id)->first();
        $count = 1; 

        $total = Student::where('membership_id', $membership_id)->where('level_id', $level_id)->count();
        $totalactive = Student::where('status','Active')->where('membership_id', $membership_id)->where('level_id', $level_id)->count();
        $totaldeactive = Student::where('status','Deactive')->where('membership_id', $membership_id)->where('level_id', $level_id)->count();
        $totalbreak = Student::where('status','Break')->where('membership_id', $membership_id)->where('level_id', $level_id)->count();
        $totalstop = Student::where('status','Stop')->where('membership_id', $membership_id)->where('level_id', $level_id)->count();
        $totalpending = Student::where('status','Pending')->where('membership_id', $membership_id)->where('level_id', $level_id)->count();
        $totalendmembership = Student::where('status','End-Membership')->where('membership_id', $membership_id)->where('level_id', $level_id)->count();
        $totalupgrade = Student::where('status','Upgrade')->where('membership_id', $membership_id)->where('level_id', $level_id)->count();
        $totaldowngrade = Student::where('status','Downgrade')->where('membership_id', $membership_id)->where('level_id', $level_id)->count();
        $totalterminate = Student::where('status','Terminate')->where('membership_id', $membership_id)->where('level_id', $level_id)->count();

        
        return view('admin.membership.view', compact('student', 'membership', 'membership_level', 'total', 'totalactive', 'totaldeactive', 'count', 'totalbreak', 'totalstop','totalpending','totalendmembership', 'totalupgrade', 'totaldowngrade', 'totalterminate'));
    }

    // search buyer
    public function search_membership($membership_id, $level_id, Request $request)
    {   
        // $student = Student::where('membership_id', $membership_id)->where('level_id', $level_id)->paginate(50);
        $membership = Membership::where('membership_id', $membership_id)->first();
        $membership_level = Membership_Level::where('membership_id', $membership_id)->where('level_id', $level_id)->first();
        // $student = Student::orderBy('id','desc')->get();

        //Count the data
        $count = 1; 
        $total = Student::where('membership_id', $membership_id)->where('level_id', $level_id)->count();
        $totalactive = Student::where('status','Active')->where('membership_id', $membership_id)->where('level_id', $level_id)->count();
        $totaldeactive = Student::where('status','Deactive')->where('membership_id', $membership_id)->where('level_id', $level_id)->count();

        //get details from search
        $student_id = Student::where('ic', $request->search)->orWhere('first_name', $request->search)->orWhere('last_name', $request->search)->orWhere('email', $request->search)->first();

        if ($student_id == NULL)
        {

            return redirect()->back()->with('search-error', 'Student not exist!');

        }else{
            
            $stud_id = $student_id->stud_id;

            $student = Student::where('stud_id','LIKE','%'. $stud_id.'%')->where('membership_id', $membership_id)->where('level_id', $level_id)->get();

            if(count($student) > 0)
            {   
                $total = Student::where('membership_id', $membership_id)->where('level_id', $level_id)->count();
                $totalactive = Student::where('status', 'Active')->where('membership_id', $membership_id)->where('level_id', $level_id)->count();
                $totaldeactive = Student::where('status', 'Deactive')->where('membership_id', $membership_id)->where('level_id', $level_id)->count();
                $totalbreak = Student::where('status', 'Break')->where('membership_id', $membership_id)->where('level_id', $level_id)->count();
                $totalstop = Student::where('status', 'Stop')->where('membership_id', $membership_id)->where('level_id', $level_id)->count();
                $totalpending = Student::where('status', 'Pending')->where('membership_id', $membership_id)->where('level_id', $level_id)->count();
                $totalendmembership = Student::where('status', 'End-Membership')->where('membership_id', $membership_id)->where('level_id', $level_id)->count();
                $totalupgradepro = Student::where('status', 'Upgrade-Pro')->where('membership_id', $membership_id)->where('level_id', $level_id)->count();
                $totalterminate = Student::where('status', 'Terminate')->where('membership_id', $membership_id)->where('level_id', $level_id)->count();

                return view('admin.membership.view', compact('student', 'membership', 'membership_level', 'total', 'totalactive', 'totaldeactive', 'count', 'totalbreak', 'totalstop','totalpending','totalendmembership', 'totalupgradepro', 'totalterminate'));

            }else{

                return redirect()->back()->with('search-error', 'Student not found!');

            }

        }
        
    }
    
    public function track_members($membership_id, $level_id, $student_id, Request $request)
    {

        $student = Student::where('membership_id', $membership_id)->where('level_id', $level_id)->where('stud_id', $student_id)->first();
        $level = Membership_level::where('membership_id', $membership_id)->where('level_id', $level_id)->first();
        $invoice = Invoice::where('student_id', $student_id)->get();
        $membership = Membership::where('membership_id', $membership_id)->first();
        $membership_level = Membership_Level::where('membership_id', $membership_id)->where('level_id', $level_id)->first();

        // due date formula add 7 days from billing date
        $date_receive = date('d-m-Y');
        $daystosum = '7';
        $datesum = date('d-m-Y', strtotime($date_receive.' + '.$daystosum.' days'));

        $student = Student::where('stud_id',$student_id)->first();

        $date1 = $request->fromDate;
        $date2 = $request->to;

        $searchbydate = DB::table('invoices')
                    ->whereBetween('created_at', [$date1, $date2])
                    ->get();

        /*
        |--------------------------------------------------------------------------
        | Display Invoices 
        |--------------------------------------------------------------------------
        */

        // keluarkan senarai invois
        $invoices = Invoice::where('student_id', $student->id)->paginate(10);

        //dapatkan membership detail
        $membership_levels = Membership_Level::where('level_id', $level_id)->first();

        $no = 1;

        /*
        |--------------------------------------------------------------------------
        | Display Receipt
        |--------------------------------------------------------------------------
        */

        $payment = Payment::where('stud_id', $student_id)->where('status', 'paid')->orderBy('created_at', 'DESC')->paginate(10);

        $payment_data = [];
        $type = [];

        foreach ($payment as $pt) {
            if ($pt->product_id != (null || '')) {
                $product1 = Product::where('product_id', $pt->product_id);

                if ($product1->count() > 0) {
                    $product1 = $product1->first();
                    $payment_data[] = $product1;
                    $type[] = 'Event';
                }

            } elseif ($pt->level_id != (null || '')) {
                $level1 = Membership_Level::where('level_id', $pt->level_id);

                if ($level1->count() > 0) {
                    $level1 = $level1->first();
                    $payment_data[] = $level1;
                    $type[] = 'Membership';
                }
            }
        }

        return view('admin.membership.view_member', compact('searchbydate', 'date1', 'membership', 'membership_level', 'student', 'no', 'membership_level', 'invoices', 'payment_data', 'type', 'payment', 'datesum'));

    }

    public function update_members($membership_id, $level_id, $student_id, Request $request)
    {
        $student = Student::where('membership_id', $membership_id)->where('level_id', $level_id)->where('stud_id', $student_id)->first();
        $membership = Membership::where('membership_id', $membership_id)->first();
        $membership_level = Membership_Level::where('membership_id', $membership_id)->where('level_id', $level_id)->first();

        $student->ic = $request->ic;
        $student->phoneno = $request->phoneno;
        $student->first_name = ucwords(strtolower($request->first_name));
        $student->last_name = ucwords(strtolower($request->last_name));
        $student->email = $request->email;
        $student->status = $request->status;
        $student->save();

        return redirect('membership/level/'.$membership_id.'/'.$level_id)->with('updatesuccess', 'Customer successfully updated');

    }

    public function destroy($membership_id, $level_id, $student_id) 
    {
        $student = Student::where('membership_id', $membership_id)->where('level_id', $level_id)->where('stud_id', $student_id);
        $payment = Payment::where('stud_id', $student_id);
        $ticket = Ticket::where('stud_id', $student_id);

        $student->delete();
        $payment->delete();
        $ticket->delete();

        return redirect('membership/level/'.$membership_id.'/'.$level_id)->with('delete-member', 'Customer successfully deleted');
    }

    public function import($membership_id, $level_id)
    {
        $membership = Membership::where('membership_id', $membership_id)->first();
        $membership_level = Membership_Level::where('membership_id', $membership_id)->where('level_id', $level_id)->first();

        return view('admin.membership.import', compact('membership', 'membership_level'));
    }

    public function export_format($product_id, $package_id)
    {
        return Excel::download(new MembersFormat, 'Membership.xlsx');
    }

    public function store_import($membership_id, $level_id)
    {
        $membership = Membership::where('membership_id', $membership_id)->first();
        $membership_level = Membership_Level::where('membership_id', $membership_id)->where('level_id', $level_id)->first();

        Excel::import(new MembershipImport($membership_id, $level_id), request()->file('file'));    
        
        return redirect('membership/level/'.$membership_id.'/'.$level_id)->with('importsuccess', 'The file has been inserted to queue, it may take a while to successfully import.');

    }

    public function store_members($membership_id, $level_id, Request $request)
    {
        $student = Student::where('ic', $request->ic)->first();
        
        if(Student::where('ic', $request->ic)->exists()){

            $student->ic = $request->ic;
            $student->phoneno = $request->phoneno;
            $student->first_name = ucwords(strtolower($request->first_name));
            $student->last_name = ucwords(strtolower($request->last_name));
            $student->email = $request->email;
            $student->membership_id = $membership_id;
            $student->level_id = $level_id;
            $student->status = 'Active';
            $student->save();

        }else{

            $stud_id = 'MI'.uniqid();
            
            Student::create(array(
                'stud_id'=> $stud_id,
                'first_name'=> ucwords(strtolower($request->first_name)),
                'last_name'=> ucwords(strtolower($request->last_name)),
                'ic' => $request->ic,
                'phoneno' => $request->phoneno,
                'email' => $request->email,
                'membership_id' => $membership_id,
                'level_id' => $level_id,
                'status' => 'Active'
            ));

        }

        return redirect('membership/level/'.$membership_id.'/'.$level_id)->with('addsuccess', 'Customer successfully added');

    }

    public function downloadInvoices($level, $invoice, $student)
    {
        $stud_detail = Student::where('stud_id', $student)->first();

        $payment_id_student = Payment::where('level_id', $level)->first();
        $member = Membership_level::where('level_id', $level)->first();
        $invoiceid = Invoice::where('student_id', $stud_detail->id)->where('invoice_id', $invoice)->first();
        $inv = Invoice::where('student_id', $stud_detail->id)->where('invoice_id', $invoice)->first();
        $no = 1;
        $balance = ($payment_id_student->totalprice)-($payment_id_student->pay_price);

        //calculation for taxes ultimate
        $subtotal = (($inv->price)+($member->add_on_price));

        // due date format
        $date_receive = date('d-m-Y');
        $daystosum = '7';
        $datesum = date('d-m-Y', strtotime($date_receive.' + '.$daystosum.' days'));

        $data['subtotal'] = $subtotal;
        $data['inv'] = $inv;
        $data['name'] = $stud_detail->first_name; //
        $data['secondname'] = $stud_detail->last_name; //
        $data['invoice'] = $invoiceid->invoice_id; //
        $data['datesum'] = $datesum; //
        $data['no'] = $invoiceid->id; //
        $data['price'] = $payment_id_student->pay_price; // 
        $data['balance']=$balance; // 
        $data['quantity'] = $payment_id_student->quantity; //
        $data['date_receive'] = date('d/m/Y', strtotime($invoiceid->created_at)); //
        $data['due_date'] = $invoiceid->due_date;
        $data['bulan'] = date('M Y'); //
        $data['member'] = $member; // 
        $data['membership'] = $member->name; // 

        $pdf = PDF::loadView('emails.downloadinvoice', $data);
        return $pdf->stream('Invoice.pdf');

    }

    public function downloadReceipt($level, $payment, $student)
    {
        $stud_detail = Student::where('stud_id', $student)->first();
        $payment_id_student = Payment::where('level_id', $level)->first();
        $member = Membership_level::where('level_id', $level)->first();
        $invoice_id = Invoice::where('student_id', $stud_detail->id)->get();
        $inv = Invoice::where('student_id', $stud_detail->id)->where('status','paid')->get();
        $receipt = Payment::where('stud_id',$stud_detail->stud_id)->where('payment_id', $payment)->first();
        $payment = Payment::where('payment_id', $payment)->where('stud_id', $stud_detail->stud_id)->first();

        //dapatkan membership_id student
        $payment_student_id = $stud_detail->level_id;
        $invoice_student = $stud_detail->id;
        $member_student = $stud_detail->level_id;

        $payment_id_student = Payment::where('level_id', $payment_student_id)->first();
        $payment = Payment::where('level_id', $payment_student_id)->first();
        $invoice_id = Invoice::where('student_id', $invoice_student)->first();

        $no = 1;
        $balance = ($payment_id_student->totalprice)-($payment_id_student->pay_price);

        $data['receipt'] = $receipt;
        $data['inv'] = $inv;
        $data['payment'] = $payment;
        $data['name']=$stud_detail->first_name; //
        $data['secondname']=$stud_detail->last_name; //
        $data['invoice']=Invoice::where('student_id', $invoice_student)->get(); //
        $data['date']=$invoice_id->for_date; //
        $data['no']=$invoice_id->id; //
        $data['invid']=$invoice_id->invoice_id; //
        $data['total']=$payment_id_student->totalprice; //
        $data['pay_id']=$payment_id_student->payment_id; //
        $data['method']=$payment_id_student->pay_method; //
        $data['billplz']=$payment_id_student->billplz_id; //
        $data['quantity']=$payment_id_student->quantity; //

        $pdf = PDF::loadView('emails.resitmember', $data);
        return $pdf->stream('Receipt.pdf');
    }

    public function uploadcheque($membership_id, $level_id, $stud_id, Request $request)
    {
        $student = Student::where('membership_id', $membership_id)->where('level_id', $level_id)->where('stud_id', $stud_id)->first();
        $level = Membership_level::where('membership_id', $membership_id)->where('level_id', $level_id)->first();
        $membership = Membership::where('membership_id', $membership_id)->first();
        $membership_level = Membership_Level::where('membership_id', $membership_id)->where('level_id', $level_id)->first();
        
        $payment_id = 'OD'.uniqid();
        $payment = Payment::where('stud_id', $student_id)->where('status', 'paid')->orderBy('created_at', 'DESC')->paginate(10);
        // dd($request->bankname);
        
        if ($membership_level->name == 'Ultimate Plus') {
            Payment::create(array(
                'payment_id'=> $payment_id,
                'pay_price'=> $request->price,
                'totalprice'=> $request->price,
                'quantity' => '1',
                'status' => 'paid',
                'pay_method' => 'Cheque',
                'email_status'  => 'Hold',
                'stud_id' => $stud_id,
                'membership_id' => 'MB001',
                'level_id' => 'MLB001',
                'offer_id' => $request->offer_id,
                'date_payment'=> $request->date_payment,
                'bankname'=> $request->bankname,
                'cheque_no'=> $request->cheque_no,
                'user_id' => Auth::user()->user_id
            ));
        } 
        elseif ($membership_level->name == 'Ultimate Partners') {
            Payment::create(array(
                'payment_id'=> $payment_id,
                'pay_price'=> $request->price,
                'totalprice'=> $request->price,
                'quantity' => '1',
                'status' => 'paid',
                'pay_method' => 'Cheque',
                'email_status'  => 'Hold',
                'stud_id' => $stud_id,
                'membership_id' => 'MB001',
                'level_id' => 'MLB002',
                'offer_id' => $request->offer_id,
                'date_payment'=> $request->date_payment,
                'bankname'=> $request->bankname,
                'cheque_no'=> $request->cheque_no,
                'user_id' => Auth::user()->user_id,
            ));
            
        }
        elseif ($membership_level->name == 'Platinum Pro') {
            Payment::create(array(
                'payment_id'=> $payment_id,
                'pay_price'=> $request->price,
                'totalprice'=> $request->price,
                'quantity' => '1',
                'status' => 'paid',
                'pay_method' => 'Cheque',
                'email_status'  => 'Hold',
                'stud_id' => $stud_id,
                'membership_id' => 'MB002',
                'level_id' => 'MLB003',
                'offer_id' => $request->offer_id,
                'date_payment'=> $request->date_payment,
                'bankname'=> $request->bankname,
                'cheque_no'=> $request->cheque_no,
                'user_id' => Auth::user()->user_id,
            ));
        }
        else {
            
            Payment::create(array(
                'payment_id'=> $payment_id,
                'pay_price'=> $request->price,
                'totalprice'=> $request->price,
                'quantity' => '1',
                'status' => 'paid',
                'pay_method' => 'Cheque',
                'email_status'  => 'Hold',
                'stud_id' => $stud_id,
                'membership_id' => 'MB002',
                'level_id' => 'MLB004',
                'offer_id' => $request->offer_id,
                'date_payment'=> $request->date_payment,
                'bankname'=> $request->bankname,
                'cheque_no'=> $request->cheque_no,
                'user_id' => Auth::user()->user_id,
            ));
        }
        
        return redirect('view/members/' . $membership_id . '/' . $level_id . '/' . $stud_id )->with('sent-success', 'Cheque has been inserted') ;
    }

    public function manualStatement()
    {
        return view('admin.membership.manualstatement');
    }

    public function Statementsave(Request $request)
    {
        $key = $request['date'];
        $data['key'] = count($key);
        $name = $request['custname'];

        // details of customer
        $data['name'] = $request['custname']; //
        $data['date1'] = $request['date1'];
        $data['date2'] = $request['date2'];
        $data['invoice_amount'] = $request['invoice_amount']; //
        $data['amountreceived'] = $request['amountreceived'];
        // dd($request['date']);
        // invoice and payment
        $data['date'] = $request['date']; //
        $data['transaction'] = $request['transaction']; //
        $data['details'] = $request['details']; //
        $data['amount'] = $request['amount']; //
        $data['payment'] = $request['payment']; //
        $data['balance'] = $request['balance']; //

        // balance 
        $data['balancedue'] = $request['balancedue'];

        $pdf = PDF::loadView('admin.manual_edit.editstatement', $data);
        return $pdf->download( $name.' Statement.pdf' );

        return view('admin.membership.membership');
    }

    public function manualReceipt()
    {
        return view('admin.membership.manualreceipt');
    }

    public function Receiptsave(Request $request)
    {
        $key = $request['receiptnumber'];
        $data['key'] = count($key);
        $name = $request['receiptfrom'];

        // student information
        $data['receiptfrom'] = $request['receiptfrom'];
        $data['receiptno'] = $request['receiptno'];
        $data['date1'] = $request['date1'];
        $data['referencenumber'] = $request['referencenumber'];
        $data['paymentmethod'] = $request['paymentmethod'];

        // information of receipt
        $data['receiptnumber'] = $request['receiptnumber'];
        $data['receiptdate'] = $request['receiptdate'];
        $data['paymentamount'] = $request['paymentamount'];

        // total already paid
        $data['subtotal'] = $request['subtotal'];

        $pdf = PDF::loadView('admin.manual_edit.editreceipt', $data);
        return $pdf->download( $name.' Receipt.pdf');

        return view('admin.membership.manualreceipt');
    }

    public function manualInvoice($membership_id, $level_id, $student_id)
    {
        $student = Student::where('membership_id', $membership_id)->where('level_id', $level_id)->where('stud_id', $student_id)->first();
        $level = Membership_level::where('membership_id', $membership_id)->where('level_id', $level_id)->first();
        $invoice = Invoice::where('student_id', $student_id)->get();
        $membership = Membership::where('membership_id', $membership_id)->first();
        $membership_level = Membership_Level::where('membership_id', $membership_id)->where('level_id', $level_id)->first();
        $productfeatures = Product_Features::all();

        return view('admin.membership.manualinvoice', compact('student', 'level', 'invoice', 'membership', 'membership_level', 'productfeatures'));
    }

    public function Invoicesave(Request $request , $stud_id)
    {
        $productfeat = Product_Features::all();
        $student = Student::where('stud_id', $stud_id)->first();
        // dd($student->id);

        $total = $request['subtotal'];
        $tax = $request['sst'];
        $totaltax = (($tax/100)*$total);
        $key = $request['no'];
        $name = $request['to'];
        $get = $request['pfeatures'];

        $data['key'] = count($key);
        
        // student information
        $data['name'] = $request['to']; //
        $data['invoice_id'] = $request['invoiceno']; //
        $data['inv_date'] = $request['date1']; //
        $data['due_date'] = $request['date2']; //
        $data['membership'] = $request['membership']; //

        // information for invoices
        $data['no'] = $request['no']; 

        foreach ($productfeat as $pff){
            foreach ($request['pfeatures'] as $pfval) { 
                if ($pff->product_features_name == $pfval){
                    $data['description'] = $request['pfeatures'];
                }
            }
        }

        $data['quantity'] = $request['quantity']; 
        $data['rate'] = $request['rate']; 
        $data['amount'] = $request['amount']; 

        // total to pay and tax
        $data['subtotal'] = $request['subtotal'];
        $data['taxableamount'] = $request['taxableamount'];
        $data['sst'] = $request['sst'];
        $data['totaltax'] = $totaltax;

        // convert array to json for storing database
        $arrayquantity = $request->quantity;
        $quantity_array = json_encode($arrayquantity);

        // convert array to json for storing database
        $array = $request->pfeatures;
        $features_array = json_encode($array);

        // convert array to json for storing database
        // $arrayid = $request->pfeatures;
        // $id_array = json_encode($array);

        // insert data to database
        Invoice::create(array(
            'invoice_id' => $request->invoiceno,
            'price' => $request['subtotal'],
            'for_date' => $request->date1,
            'status' => 'not paid',
            'student_id' => $student->id,
            'quantity' => $quantity_array,
            'tax' => $request->sst,
            'taxable_amount' => $request->taxableamount,
            'product_features_name' => $features_array
        ));

        // download function by use editinvoice template
        $pdf = PDF::loadView('admin.manual_edit.editinvoice', $data)->setPaper('a4');
        return $pdf->download( $name.' Invoice.pdf' );

        // return redirect('view/members/' . $membership_id . '/' . $level_id . '/' . $stud_id )->with('success', 'Invoice Successfully Created');
    }

    public function addFeaturesProduct(Request $request)
    {
        $features = Product_Features::orderBy('id','desc')->first();

        $auto_inc_fp = $features->id + 1;
        $features_product_id = 'FP' . 0 . $auto_inc_fp ;

        Product_Features::create(array(
            'product_features_name' => $request['name'],
            'product_features_id' => $features_product_id,
            'features_price' => $request['price'],
            'description_features' => $request['description'],
            'features_tax' => $request['tax']
        ));

        return redirect('membership')->with('success', 'Features Successfully Created'); 
    }

    public function downloadManualInvoices($level, $invoice, $student)
    {

        $stud_detail = Student::where('stud_id', $student)->first();

        $payment_id_student = Payment::where('level_id', $level)->first();
        $member = Membership_level::where('level_id', $level)->first();
        $invoiceid = Invoice::where('student_id', $stud_detail->id)->where('invoice_id', $invoice)->first();
        $inv = Invoice::where('student_id', $stud_detail->id)->where('invoice_id', $invoice)->first();
        $no = 1;
        $balance = ($payment_id_student->totalprice)-($payment_id_student->pay_price);

        // table product features
        $listfeatures = Product_Features::all();
        foreach ($listfeatures as $listfeat => $listval){
            $listoffeat = $listval;
        }

        // quantity method decode untuk keluarkan dalam array
        $quantity_array = $invoiceid->quantity;
        $arrayquan = json_decode($quantity_array,true);

        // description method decode untuk keluarkan dalam array
        $prodfeatures = $invoiceid->product_features_name;
        $arrayfeat = json_decode($prodfeatures,true);

        //calculation for taxes ultimate
        $subtotal = (($inv->price)+($member->add_on_price));

        // due date format
        $date_receive = date('d-m-Y');
        $daystosum = '7';
        $datesum = date('d-m-Y', strtotime($date_receive.' + '.$daystosum.' days'));
        dd($datesum);

        $data['subtotal'] = $subtotal;
        $data['inv'] = $inv;
        $data['name'] = $stud_detail->first_name; //
        $data['secondname'] = $stud_detail->last_name; //
        $data['invoices'] = $invoiceid; //
        $data['invoice'] = $invoiceid->invoice_id; //
        // $data['arrays'] = $newArray;
        $data['arrayquan'] = $arrayquan;
        $data['arrayfeat'] = $arrayfeat;
        $data['listoffeatures'] = $listfeatures;
        $data['datesum'] = $datesum; //
        $data['no'] = $invoiceid->id; //
        $data['price'] = $payment_id_student->pay_price; // 
        $data['balance']=$balance; // 
        $data['quantity'] = $payment_id_student->quantity; //
        $data['date_receive'] = date('d/m/Y', strtotime($invoiceid->created_at)); //
        $data['due_date'] = $invoiceid->due_date;
        $data['bulan'] = date('M Y'); //
        $data['member'] = $member; // 
        $data['membership'] = $member->name; // 

        $pdf = PDF::loadView('emails.downloadmanualinvoice', $data);
        return $pdf->stream('Invoice.pdf');

        ////////////////////////////////////////////////////////////////////////////////////////

        // $stud_detail = Student::where('stud_id', $student)->first();

        // $payment_id_student = Payment::where('level_id', $level)->first();
        // $member = Membership_level::where('level_id', $level)->first();
        // $invoiceid = Invoice::where('student_id', $stud_detail->id)->where('invoice_id', $invoice)->first();
        // $inv = Invoice::where('student_id', $stud_detail->id)->where('invoice_id', $invoice)->first();
        // $no = 1;
        // $balance = ($payment_id_student->totalprice)-($payment_id_student->pay_price);

        // // table product features
        // $listfeatures = Product_Features::all();
        // foreach ($listfeatures as $listfeat => $listval){
        //     $listoffeat = $listval;
        // }

        // // quantity method decode untuk keluarkan dalam array
        // $quantity_array = $invoiceid->quantity;
        // $arrayquan = json_decode($quantity_array,true);

        // // description method decode untuk keluarkan dalam array
        // $prodfeatures = $invoiceid->product_features_name;
        // $arrayfeat = json_decode($prodfeatures,true);

        // //calculation for taxes ultimate
        // $subtotal = (($inv->price)+($member->add_on_price));

        // // due date format
        // $date_receive = date('d-m-Y');
        // $daystosum = '7';
        // $datesum = date('d-m-Y', strtotime($date_receive.' + '.$daystosum.' days'));

        // $data['subtotal'] = $subtotal;
        // $data['inv'] = $inv;
        // $data['name'] = $stud_detail->first_name; //
        // $data['secondname'] = $stud_detail->last_name; //
        // $data['invoice'] = $invoiceid->invoice_id; //
        // $data['quantity'] = $arrayquan;
        // $data['description'] = $arrayfeat;
        // $data['listoffeatures'] = $listfeatures;
        // $data['datesum'] = $datesum; //
        // $data['no'] = $invoiceid->id; //
        // $data['price'] = $payment_id_student->pay_price; // 
        // $data['balance']=$balance; // 
        // $data['quantity'] = $payment_id_student->quantity; //
        // $data['date_receive'] = date('d/m/Y', strtotime($invoiceid->created_at)); //
        // $data['due_date'] = $invoiceid->due_date;
        // $data['bulan'] = date('M Y'); //
        // $data['member'] = $member; // 
        // $data['membership'] = $member->name; // 

        // $pdf = PDF::loadView('emails.downloadmanualinvoice', $data);
        // return $pdf->stream('Invoice.pdf');

    }

    public function searchbydate($membership_id, $level_id, $student_id, Request $request)
    {

        $student = Student::where('stud_id',$student_id)->first();

        $date1 = $request->fromDate;
        $date2 = $request->to;
    
        // $date1 = '2021-01-01';
        // $date2 = '2022-10-17';

        $searchbydate = DB::table('invoices')
                    ->whereBetween('for_date', [$date1, $date2])
                    ->get();
                    
        // $searchbydate = DB::table('invoices')
        //             ->whereBetween('created_at', [$date1, $date2])
        //             ->get();

        // dd($searchbydate);

        // $searchbydate = Invoice::where('student_id', $student)->whereBetween('for_date',[$date1,Carbon::parse($date2)-endOfDay(),])->get(['invoice_id']);
        // $searchbydate = DB::table('invoices')->select()->where('for_date','>=',$date1)
        //                                                 ->where('for_date','<=',$date2)->get();

        // $searchbydate = DB::table('invoices')->select()->where('created_at','>=',$date1)
        // ->where('created_at','<=',$date2)->get();

        ////////////////////////////////////////////////////////////////////////////////////////////////

        $student = Student::where('membership_id', $membership_id)->where('level_id', $level_id)->where('stud_id', $student_id)->first();
        $level = Membership_level::where('membership_id', $membership_id)->where('level_id', $level_id)->first();
        $invoice = Invoice::where('student_id', $student_id)->get();
        $membership = Membership::where('membership_id', $membership_id)->first();
        $membership_level = Membership_Level::where('membership_id', $membership_id)->where('level_id', $level_id)->first();

        // due date formula add 7 days from billing date
        $date_receive = date('d-m-Y');
        $daystosum = '7';
        $datesum = date('d-m-Y', strtotime($date_receive.' + '.$daystosum.' days'));

        /*
        |--------------------------------------------------------------------------
        | Display Invoices 
        |--------------------------------------------------------------------------
        */

        // keluarkan senarai invois
        $invoices = Invoice::where('student_id', $student->id)->paginate(10);

        //dapatkan membership detail
        $membership_levels = Membership_Level::where('level_id', $level_id)->first();

        $no = 1;

        /*
        |--------------------------------------------------------------------------
        | Display Receipt
        |--------------------------------------------------------------------------
        */

        $payment = Payment::where('stud_id', $student_id)->where('status', 'paid')->orderBy('created_at', 'DESC')->paginate(10);

        $payment_data = [];
        $type = [];

        foreach ($payment as $pt) {
            if ($pt->product_id != (null || '')) {
                $product1 = Product::where('product_id', $pt->product_id);

                if ($product1->count() > 0) {
                    $product1 = $product1->first();
                    $payment_data[] = $product1;
                    $type[] = 'Event';
                }

            } elseif ($pt->level_id != (null || '')) {
                $level1 = Membership_Level::where('level_id', $pt->level_id);

                if ($level1->count() > 0) {
                    $level1 = $level1->first();
                    $payment_data[] = $level1;
                    $type[] = 'Membership';
                }
            }
        }

        return view('admin.membership.view_member', compact('searchbydate', 'date1', 'membership', 'membership_level', 'student', 'no', 'membership_level', 'invoices', 'payment_data', 'type', 'payment', 'datesum'));
    }
}
