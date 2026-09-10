import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;



@Component({
  selector: 'app-artworklog',
  templateUrl: './artworklog.component.html',
  styleUrls: ['./artworklog.component.css']
})
export class ArtworklogComponent implements OnInit {
  
  
  results;

  selectedResult=[];

  isNew=false


  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getArtworkDataLog();
    this.getProduct();
    this.getPrintedMaterials();
  }

  getArtworkDataLog(){
    this.service.get('qa/artwork.php?type=getActiveArtwork').subscribe(response=>{
      this.results=response;
    })
  }

  materials;
  Products;

  getProduct(){
    this.service.get('qa/artwork.php?type=getProduct').subscribe(response=>{
      this.Products = response;
    })
  }

  getPrintedMaterials(){
    this.service.get('qa/artwork.php?type=getPrintedMaterials').subscribe(response=>{
      this.materials=response;
    })
  }


  version_no =1;

  isNanValue(){
    if(isNaN(this.version_no)){
      alertify.error('Please Enter Version Number For EX. 1,2,3....' );
      this.version_no = 0;
    }
  }


  artwork: File;
  onFileChange2($event) {
    this.artwork = $event.target.files[0];
  }

  fileCdr: File;
  onFileChange3($event) {
    this.fileCdr = $event.target.files[0];
  }


  saveForm(data){

    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    let temp=data.value;
    
    const formData = new FormData();

     for (let key in temp) {
      let value = temp[key];
      formData.append(key, value);
    }

    if (this.artwork !== undefined && this.artwork !== null) {
      formData.append('artwork', this.artwork, this.artwork.name);
    }
    if (this.fileCdr !== undefined && this.fileCdr !== null) {
      formData.append('fileCdr', this.fileCdr, this.fileCdr.name);
    }

      this.service.post('qa/artwork.php?type=saveExistingArtwork',formData).subscribe(response => {
        if (response['status'] == 'success') {
          alertify.success('Record updated successfully');
          this.isNew = false;
          this.getArtworkDataLog();
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
  }
   
  fileView(url){
    url = this.service.url + '../../upload/artwork/' + url;
    window.open(url, '_blank');
  }
 


  artworkInactive(item){


    this.selectedResult = item;


    this.service.post('qa/artwork.php?type=reuploadArtwork&ID='+this.selectedResult['id'],JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.getArtworkDataLog()
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    }); 
  }

 


}
