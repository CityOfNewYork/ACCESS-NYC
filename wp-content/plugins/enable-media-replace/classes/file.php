<?php
namespace EnableMediaReplace;

class emrFile
{

  protected $file; // the full file w/ path.
  protected $extension;
  protected $fileName;
  protected $filePath;
  protected $fileURL;
  protected $fileMime;
  protected $permissions = 0;

  protected $exists = false;

  public function __construct($file)
  {
      clearstatcache($file);
      // file can not exist i.e. crashed files replacement and the lot.
     if ( file_exists($file))
     {
       $this->exists = true;
     }

     $this->file = $file;
     $fileparts = pathinfo($file);

     $this->fileName = isset($fileparts['basename']) ? $fileparts['basename'] : '';
     $this->filePath = isset($fileparts['dirname']) ? $fileparts['dirname'] : '';
     $this->extension = isset($fileparts['extension']) ? $fileparts['extension'] : '';
     if ($this->exists) // doesn't have to be.
      $this->permissions = fileperms($file) & 0777;

     $filedata = wp_check_filetype_and_ext($this->file, $this->fileName);
     // This will *not* be checked, is not meant for permission of validation!
     $this->fileMime = (isset($filedata['type'])) ? $filedata['type'] : false;

    // echo "<PRE>"; var_dump($this); echo "</PRE><BR>";
  }

  public function getFullFilePath()
  {
    return $this->file;
  }

  public function getPermissions()
  {
    return $this->permissions;
  }

  public function setPermissions($permissions)
  {
    @chmod($this->file, $permissions);
  }

  public function getFilePath()
  {
    return $this->filePath;
  }

  public function getFileName()
  {
    return $this->fileName;
  }

  public function getFileMime()
  {
    return $this->fileMime;
  }


}


?>
