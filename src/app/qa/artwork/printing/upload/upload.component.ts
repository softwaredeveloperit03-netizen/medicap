import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-upload',
  templateUrl: './upload.component.html',
  styleUrls: ['./upload.component.css']
})
export class UploadComponent implements OnInit {
  results;
  isView=false;
  selectedResult=[];
  products;
  vendors;
  selectedFile: File;

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getArtworksLog();
  }
  getArtworksLog(){
    this.service.get('qa/artwork.php?type=getAwaitingPrinting').subscribe(response=>{
      this.results=response;
    })
  }
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }
  opendoc(url){
    url=this.service.url;
    window.open(url+this.selectedResult['file']);
    // this.service.open('qa/artwork.php=getArtworksLog&id='+this.selectedResult['id']);
  }
  openFile(url){
    url=this.service.url;
    window.open(url+this.selectedResult['design_file']);
  }
  
  onFileChanged(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile = event.target.files[0];
    }
  }
  save(data){
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    const uploadData = new FormData();

    if (this.selectedFile !== undefined) {
      uploadData.append('printing', this.selectedFile, this.selectedFile.name);
    }
    this.service.post('qa/artwork.php?type=uploadPrinting&id=' + this.selectedResult['id'],uploadData).subscribe(response=>{
      alert("saved Successfully");
      data.resetForm();
    });
  }


}
