<?php

namespace App\Http\Controllers;

use Auth;
use App\news;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class newsController extends Controller
{
	public function show()
	{
		$managementArea = \App\managementArea::firstOrFail();
		$userId = Auth::user()->user_id;
		$userType = Auth::user()->user_type;
		if ($userId!=1){//Logueado y si no es administrador
			if($userType==4){//si es empresa
				$newsTable = news::where('news_user',$userId)->orderBy('updated_at', 'desc')->get();
			}else{
				$newsTable = news::orderBy('updated_at', 'desc')->get();
			}
		}else{//No logueado
			$newsTable = news::orderBy('updated_at', 'desc')->get();
		}
		if($userType!=4){
			$newsTypeTable = \App\newsType::All();
		}else {
			$newsTypeTable = \App\newsType::where('news_type_id',3)->get();
		}
		$multimediaTable = \App\multimediaType::All();
		LaravelLocalization::getSupportedLocales();
		return view ('admin.news')
		->withManagement($managementArea)
		->withNews($newsTable)
		->withMultimedia($multimediaTable)
		->withTypes($newsTypeTable);
	}

	public function showData($newsId)
	{
		$managementArea = \App\managementArea::firstOrFail();
		$news =  \App\news::find($newsId);
		$multimedia = \App\multimedia::where('multimedia_news',$newsId)
		->orderBy('multimedia_type', 'asc')
		->orderBy('multimedia_create','desc')
		->get();

		return view ('admin.newsData')
		->withManagement($managementArea)
		->withNews($news)
		->withMultimedia($multimedia);
	}

	public function store(Request $request)
	{

		$this->validate($request,[

			'newsTitle' => 'required|unique:news,news_title|max:100',
			'newsAlias' => 'required|unique:news,news_alias|max:100',
			'newsDescription' => 'required',
			'newsType' => 'required',
			]);

		$news = new news();
		/*Controlar segun tipo de usuario*/
		$userId = Auth::user()->user_id;
		$userType = Auth::user()->user_type;
		if($userType==4){
			$news->news_user=$userId;
			$news->news_state = 0;
		}else {
			$news->news_state = 1;
		}
		$news->news_title = $request['newsTitle'];
		$news->news_content = $request['newsDescription'];

		$news->news_author = Auth::user()->user_id;
		$news->news_alias = $request['newsAlias'];
		$news->news_type = $request['newsType'];
		$news->news_management_area = 1;

		$news->save();

		unset($request);
		unset($news);

		return back()->withMensaje('Operación Exitosa');

	}

	public function delete (Request $request){
		try{
			$news = news::find($request['newsId']);
			$news->news_state   = 0;
			$news->save();
			unset($request);
			unset($news);
			return back()->withMensaje('Operación Exitosa');

		}catch(\Exception $e){
			return back()->withMensaje('Error en la operación');

		}

	}

	public function update (Request $request){

		$news = news::find($request['newsId']);

		$this->validate($request,[

			'newsTitle' => 'required|max:100|unique:news,news_title,'.$news->news_id.',news_id',
			'newsAlias'=> 'required|max:100|unique:news,news_alias,'.$news->news_id.',news_id',
			'newsDescription' => 'required',
			'newsDate' => 'date|date_format:Y-m-d',

			]);


		$news->news_title   = $request['newsTitle'];
		$news->news_content   = $request['newsDescription'];
		$news->news_alias   = $request['newsAlias'];
		if (isset($request->newsState)){
			$news->news_state   = $request['newsState'];
		}
		$news->news_create = $request['newsDate'];

		$news->save();
		unset($request);
		unset($news);
		return back()->withMensaje('Operación Exitosa');

	}


	public function addResource(Request $request){

		$newsId = $request->newsId;

		$this->validate($request,[

			'multimediaType' => 'required',
			'foto.*'=>'mimes:jpg,jpeg,png|image',
			'archivo.*'=>'mimes:pdf,docx|file',
			'enlace.*'=>'url',
			]);

		if (isset($request->foto)) {

			foreach ( $request->foto as $fotografia) {

				\App\multimedia::create([
					'multimedia_news'=> $newsId,
					'multimedia_name'=> str_replace(" ","_",strtolower ($fotografia->getClientOriginalName())),
					'multimedia_type'=> 1,
					]);

				$path = 'img/noticias';
				$filename = str_replace(" ","_",strtolower ($fotografia->getClientOriginalName()));
				$fotografia->move($path, $filename);
			}
		}

		if (isset($request->enlace)) {
			foreach ( $request->enlace as $enlace) {
				\App\multimedia::create(
					[
					'multimedia_news'=> $newsId,
					'multimedia_name'=> $enlace,
					'multimedia_url' => $enlace,
					'multimedia_type'=> 3,
					]
					);
			}
		}

		if (isset($request->archivo)) {
			foreach ( $request->archivo as $file) {

				\App\multimedia::create(
					[
					'multimedia_news'=> $newsId,
					'multimedia_name'=> $file->getClientOriginalName(),
					'multimedia_type'=> 2,
					]
					);

				$path = 'docs/noticias';
				$filename = $file->getClientOriginalName();
				$file->move($path, $filename);
			}
		}
		unset($request);
		return redirect('admin/newsData/'.$newsId);


	}

	public function deleteResource(Request $request){

		$multimediaData = \App\multimedia::find($request->multimediaId);
		if ($multimediaData->multimedia_type==1) {
			$path = 'img/noticias';
			\File::delete($path.'/'.$multimediaData->multimedia_name);
		}
		if ($multimediaData->multimedia_type==2) {
			$path = 'docs/noticias';
			\File::delete($path.'/'.$multimediaData->multimedia_name);
		}
		$multimediaData->delete();
		unset($multimediaData);
		unset($request);
		return back();
	}

}
