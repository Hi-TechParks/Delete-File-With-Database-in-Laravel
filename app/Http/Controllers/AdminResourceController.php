<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Auth;
use DB;
use Image;
use File;

class AdminResourceController extends Controller
{
   


    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function dateFormat(){
        $today = Carbon::now();
        return $today->toDateString();
    }


    public function index(Request $request)
    {
        //
        $category   = $request->get('category');
        $program   = $request->get('program');
        $title      = $request->get('title');
        $status      = $request->get('status');
        //  

        $resources = DB::table('KOSM_RESOURCE')
                    ->join('KOSM_RESOURCE_CATEGORY', 'KOSM_RESOURCE.RESOURCE_CATEGORY_ID', 'KOSM_RESOURCE_CATEGORY.RESOURCE_CATEGORY_ID')
                    ->join('KOSM_PROGRAM', 'KOSM_RESOURCE.PROGRAM_ID', 'KOSM_PROGRAM.PROGRAM_ID')
                    ->select('KOSM_RESOURCE.*','KOSM_RESOURCE_CATEGORY.RESOURCE_CATEGORY_ID', 'KOSM_RESOURCE_CATEGORY.CATEGORY_NAME', 'KOSM_PROGRAM.PROGRAM_ID', 'KOSM_PROGRAM.PROGRAM_NAME')
                    ->where('KOSM_RESOURCE_CATEGORY.RESOURCE_CATEGORY_ID','LIKE','%'.$category.'%')
                    ->where('KOSM_PROGRAM.PROGRAM_ID','LIKE','%'.$program.'%')
                    ->where('KOSM_RESOURCE.RESOURCE_NAME','LIKE','%'.$title.'%')
                    ->where('KOSM_RESOURCE.ACTIVE_STATUS','LIKE','%'.$status.'%')
                    ->orderby('KOSM_RESOURCE.RESOURCE_ID', 'DESC')
                    ->paginate(50)
                    ->appends(['category' => $category, 'program' => $program, 'title' => $title, 'status' => $status]);

        $categories = DB::select('SELECT * FROM KOSM_RESOURCE_CATEGORY WHERE ACTIVE_STATUS = 1 ORDER BY CATEGORY_NAME');

        $programs = DB::select('SELECT * FROM KOSM_PROGRAM WHERE ACTIVE_STATUS = 1 ORDER BY PROGRAM_NAME');

        return view('dashbord_resource_list')->with('resources', $resources)
                                            ->with('categories', $categories)
                                            ->with('programs', $programs);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = DB::select('SELECT * FROM KOSM_RESOURCE_CATEGORY WHERE ACTIVE_STATUS = 1 ORDER BY CATEGORY_NAME');

        $programs = DB::select('SELECT * FROM KOSM_PROGRAM WHERE ACTIVE_STATUS = 1 ORDER BY PROGRAM_NAME');
                    
        return view('dashbord_resource_upload')->with('categories',$categories)
                                            ->with('programs', $programs);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        // Resource fields validation
        $this->validate($request,[

            'category'      => 'required',
            'title'         => 'required',
            'file_type'     => 'required',
            'resource'      => 'required',
        ]);


       // primary key generation
       $primarykey = DB::select('SELECT FNC_GETPK("KOSM_RESOURCE");');
           foreach ($primarykey as $value) {
                $result = $value;
           }
           foreach ($result as  $resource_id) {
               $result = $resource_id; // $resource_id is primary key
           }



       // File upload
        if($request->hasFile('resource')){
            $filenameWithExt = $request->file('resource')->getClientOriginalName();
            
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME); 
            $extension = $request->file('resource')->getClientOriginalExtension();
            $fileNameToStore = $filename.'_'.time().'.'.$extension;
            
            $path = $request->file('resource')->move('uploads/images/resource', $fileNameToStore);

        }else{
            $fileNameToStore = 'noimage.jpg'; // if no image selected this will be the default image
            }



        $insert = DB::table('KOSM_RESOURCE')->insert([
            'RESOURCE_ID' => $resource_id, 
            'RESOURCE_CATEGORY_ID' => $request->get('category'), 
            'PROGRAM_ID' => $request->get('program'),
            'RESOURCE_NAME' => $request->get('title'), 
            'RESOURCE_DESC' => $request->get('content'),  
            'RESOURCE_FILE_TYPE' => $request->get('file_type'), 
            'ACTIVE_STATUS' => '1',  
            'RESOURCE_FILE_PATH' => $fileNameToStore,
            'ENTERED_BY' => Auth::user()->USER_ID, // should be auth user
            'ENTRY_TIMESTAMP' => Carbon::now()
         ]);

        $categories = DB::select('SELECT * FROM KOSM_RESOURCE_CATEGORY WHERE ACTIVE_STATUS = 1 ORDER BY CATEGORY_NAME');

        $programs = DB::select('SELECT * FROM KOSM_PROGRAM WHERE ACTIVE_STATUS = 1 ORDER BY PROGRAM_NAME');

        if (isset($insert)) {
          return view('dashbord_resource_upload')->with('status','Resource Created Succesfully')
                                             ->with('categories',$categories)
                                             ->with('programs', $programs);
        }else{
          return view('dashbord_resource_upload')->with('status','Something Went Wrong Try Again later...')
                                             ->with('categories',$categories)
                                             ->with('programs', $programs);
        }
      
       
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $resource_details = DB::table('KOSM_RESOURCE')
                            ->join('KOSM_RESOURCE_CATEGORY', 'KOSM_RESOURCE.RESOURCE_CATEGORY_ID', 'KOSM_RESOURCE_CATEGORY.RESOURCE_CATEGORY_ID')
                            ->join('KOSM_PROGRAM', 'KOSM_RESOURCE.PROGRAM_ID', 'KOSM_PROGRAM.PROGRAM_ID')
                            ->select('KOSM_RESOURCE.*','KOSM_RESOURCE_CATEGORY.RESOURCE_CATEGORY_ID', 'KOSM_RESOURCE_CATEGORY.CATEGORY_NAME', 'KOSM_PROGRAM.PROGRAM_ID', 'KOSM_PROGRAM.PROGRAM_NAME')
                            ->where('KOSM_RESOURCE.RESOURCE_ID',$id)
                            -> get();


       return view('dashbord_resource_view')->with('resource_details',$resource_details);
    }




    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $resource_details = DB::table('KOSM_RESOURCE')
                            ->join('KOSM_RESOURCE_CATEGORY', 'KOSM_RESOURCE.RESOURCE_CATEGORY_ID', 'KOSM_RESOURCE_CATEGORY.RESOURCE_CATEGORY_ID')
                            ->join('KOSM_PROGRAM', 'KOSM_RESOURCE.PROGRAM_ID', 'KOSM_PROGRAM.PROGRAM_ID')
                            ->select('KOSM_RESOURCE.*','KOSM_RESOURCE_CATEGORY.RESOURCE_CATEGORY_ID', 'KOSM_RESOURCE_CATEGORY.CATEGORY_NAME', 'KOSM_PROGRAM.PROGRAM_ID', 'KOSM_PROGRAM.PROGRAM_NAME')
                            ->where('KOSM_RESOURCE.RESOURCE_ID',$id)
                            -> get();

        $categories = DB::select('SELECT * FROM KOSM_RESOURCE_CATEGORY WHERE ACTIVE_STATUS = 1 ORDER BY CATEGORY_NAME');

        $programs = DB::select('SELECT * FROM KOSM_PROGRAM WHERE ACTIVE_STATUS = 1 ORDER BY PROGRAM_NAME');

        return view('dashbord_resource_edit')->with('resource_details',$resource_details)
                                        ->with('categories',$categories)
                                        ->with('programs', $programs);

    }






    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        
        // Resource fields validation
        $this->validate($request,[

            'category'      => 'required',
            'title'         => 'required',
            'file_type'     => 'required',
        ]);


        // File upload
        if($request->hasFile('resource')){
            $filenameWithExt = $request->file('resource')->getClientOriginalName();
            
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME); 
            $extension = $request->file('resource')->getClientOriginalExtension();
            $fileNameToStore = $filename.'_'.time().'.'.$extension;

            $path = $request->file('resource')->move('uploads/images/resource', $fileNameToStore);


            // Delete Old File
            $files =  DB::table('KOSM_RESOURCE')
                      ->select('KOSM_RESOURCE.*')
                      ->where('RESOURCE_ID', $id)
                      ->get();

            foreach ($files as $file) {

                File::delete('uploads/images/resource/'.$file->RESOURCE_FILE_PATH);
            }



            $update =  DB::table('KOSM_RESOURCE')
                      ->where('RESOURCE_ID', $id)
                      ->update([
                        'RESOURCE_CATEGORY_ID' => $request->get('category'), 
                        'PROGRAM_ID' => $request->get('program'),
                        'RESOURCE_NAME' => $request->get('title'), 
                        'RESOURCE_DESC' => $request->get('content'),  
                        'RESOURCE_FILE_TYPE' => $request->get('file_type'),  
                        'ACTIVE_STATUS' => '1',  
                        'RESOURCE_FILE_PATH' => $fileNameToStore,
                        'UPDATED_BY' => Auth::user()->USER_ID, // should be auth user
                        'UPDATE_TIMESTAMP' => Carbon::now()
                    ]);
        }
        else{
                        
            $update =  DB::table('KOSM_RESOURCE')
                      ->where('RESOURCE_ID', $id)
                      ->update([
                        'RESOURCE_CATEGORY_ID' => $request->get('category'), 
                        'PROGRAM_ID' => $request->get('program'),
                        'RESOURCE_NAME' => $request->get('title'), 
                        'RESOURCE_DESC' => $request->get('content'),  
                        'RESOURCE_FILE_TYPE' => $request->get('file_type'), 
                        'ACTIVE_STATUS' => '1',  
                        'UPDATED_BY' => Auth::user()->USER_ID, // should be auth user
                        'UPDATE_TIMESTAMP' => Carbon::now()
                    ]);
        }


        $resource_details = DB::table('KOSM_RESOURCE')
                        ->join('KOSM_RESOURCE_CATEGORY', 'KOSM_RESOURCE.RESOURCE_CATEGORY_ID', 'KOSM_RESOURCE_CATEGORY.RESOURCE_CATEGORY_ID')
                        ->join('KOSM_PROGRAM', 'KOSM_RESOURCE.PROGRAM_ID', 'KOSM_PROGRAM.PROGRAM_ID')
                        ->select('KOSM_RESOURCE.*','KOSM_RESOURCE_CATEGORY.RESOURCE_CATEGORY_ID', 'KOSM_RESOURCE_CATEGORY.CATEGORY_NAME', 'KOSM_PROGRAM.PROGRAM_ID', 'KOSM_PROGRAM.PROGRAM_NAME')
                        ->where('KOSM_RESOURCE.RESOURCE_ID',$id)
                        -> get();


            return view('dashbord_resource_view')->with('status','Resource Updated Succesfully')->with('resource_details',$resource_details);
   
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Delete File
        $files =  DB::table('KOSM_RESOURCE')
                  ->select('KOSM_RESOURCE.*')
                  ->where('RESOURCE_ID', $id)
                  ->get();

        foreach ($files as $file) {

            File::delete('uploads/images/resource/'.$file->RESOURCE_FILE_PATH);
        }

        // Delete Database Info
        $delete =  DB::table('KOSM_RESOURCE')
                  ->where('RESOURCE_ID', $id)
                  ->delete();

        
        //
        return redirect()->route('resource.index', ['success' => encrypt("Reasource Delete Succesfully")]); 
    }

    // Change Slide Active Status
    public function changeStatus($id){


        $ACTIVE_STATUS = DB::SELECT(" SELECT ACTIVE_STATUS FROM KOSM_RESOURCE WHERE RESOURCE_ID = '$id'");

        foreach ($ACTIVE_STATUS as $value) {
            
            $status = $value->ACTIVE_STATUS;

        }

        if ($status == 1) {
           $update =  DB::table('KOSM_RESOURCE')
                              ->where('RESOURCE_ID', $id)
                              ->update([
                                'ACTIVE_STATUS' => 0
                            ]);
        }else{
            $update =  DB::table('KOSM_RESOURCE')
                              ->where('RESOURCE_ID', $id)
                              ->update([
                                'ACTIVE_STATUS' => 1
                            ]);
        }

        $resources = DB::table('KOSM_RESOURCE')
                            ->join('KOSM_RESOURCE_CATEGORY', 'KOSM_RESOURCE.RESOURCE_CATEGORY_ID', 'KOSM_RESOURCE_CATEGORY.RESOURCE_CATEGORY_ID')
                            ->join('KOSM_PROGRAM', 'KOSM_RESOURCE.PROGRAM_ID', 'KOSM_PROGRAM.PROGRAM_ID')
                            ->select('KOSM_RESOURCE.*','KOSM_RESOURCE_CATEGORY.RESOURCE_CATEGORY_ID', 'KOSM_RESOURCE_CATEGORY.CATEGORY_NAME', 'KOSM_PROGRAM.PROGRAM_ID', 'KOSM_PROGRAM.PROGRAM_NAME')
                            ->where('KOSM_RESOURCE.RESOURCE_ID',$id)
                            ->paginate(50);

        $categories = DB::select('SELECT * FROM KOSM_RESOURCE_CATEGORY WHERE ACTIVE_STATUS = 1 ORDER BY CATEGORY_NAME');

        $programs = DB::select('SELECT * FROM KOSM_PROGRAM WHERE ACTIVE_STATUS = 1 ORDER BY PROGRAM_NAME');

        return view('dashbord_resource_list')->with('resources', $resources)
                                            ->with('categories', $categories)
                                            ->with('programs', $programs);
    }
}
