import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;


@Component({
  selector: 'app-upload',
  templateUrl: './upload.component.html',
  styleUrls: ['./upload.component.css']
})
export class UploadComponent implements OnInit {
  results: any
      isView=false
     Products: any
     Developers: any
    materials: any
 

  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
  
    this.getArtworkDataLog()
    this.getProduct()
    this.getDevlopers()
    this.getPrintedMaterials()
    

  }

  getArtworkDataLog(){
    this.service.get('qa/artwork.php?type=getInitiateArtwork').subscribe(response=>{
      this.results=response;
    })
   
  }
 
getProduct(){
  this.service.get('qa/artwork.php?type=getProduct').subscribe(response=>{
    this.Products = response;
  })
}

 
getDevlopers(){
  this.service.get('hr/employee.php?type=getartist').subscribe(response=>{
    this.Developers=response;
  })
}

getPrintedMaterials(){
  this.service.get('qa/artwork.php?type=getPrintedMaterials').subscribe(response=>{
    this.materials=response;
  })
}


isDIGI = false;


selectedResult ;

jadugar(abcd){
  this.selectedResult = abcd;
  this.isDIGI = true;
}


artwork: File;
onFileChange2($event) {
  this.artwork = $event.target.files[0];
}


saveForm(data){

  if (!data.valid) {
    alert('All fields are required');
    return;
  }

  let temp = data.value;
   
    this.service.post('qa/artwork.php?type=initiateArtwork',JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isView = false;
        this.getArtworkDataLog();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
}


uploadArtworkFile(data){

  if (!data.valid) {
    alert('All fields are required');
    return;
  }

  let temp = this.selectedResult;
  
  const formData = new FormData();

   for (let key in temp) {
    let value = temp[key];
    formData.append(key, value);
  }

  if (this.artwork !== undefined && this.artwork !== null) {
    formData.append('artwork', this.artwork, this.artwork.name);
  }

    this.service.post('qa/artwork.php?type=saveInitiateArtworkUpload',formData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isDIGI = false;

        this.getArtworkDataLog();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
}
 
 



}
