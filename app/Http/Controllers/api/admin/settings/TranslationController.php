<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Translation;

class TranslationController extends Controller
{
    public function __construct(private Translation $translation){}

    public function view(){
        $translation = $this->translation
        ->get();

        return response()->json([
            'translation' => $translation
        ]);
    }

    public function link(){
        $link = base_path('lang\\');
        $filename = 'messages.php';

        return response()->json([
            'link' => $link,
            'filename' => $filename,
        ]);
    }

    public function create(Request $request){
        
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $this->translation
        ->create([
            'name' => $request->name
        ]);
        $directory = base_path('lang\\' . $request->name);
        
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true); // Create the directory if it doesn't exist
        }
        
        $filename = 'messages.php';
        $content = '<?php
        return [];
        ';
        file_put_contents($directory . DIRECTORY_SEPARATOR . $filename, $content);

        return response()->json([
            'success' => 'You add translation file success'
        ]);
    }

    public function delete($id){
        $translation = $this->translation
        ->where('id', $id)
        ->first();
        $directory = base_path('lang\\' . $translation->name);
        if (is_dir($directory)) {
            // Scan the directory and get all files and folders
            $files = array_diff(scandir($directory), ['.', '..']);
        
            foreach ($files as $file) {
                $path = $directory . DIRECTORY_SEPARATOR . $file;
                // Recursively delete if it's a directory
                if (is_dir($path)) {
                    deleteDirectory($path);
                } else {
                    // Delete the file
                    unlink($path);
                }
            }
            rmdir($directory);
        }
        $translation->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
