import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-upload',
  templateUrl: './upload.component.html',
  styleUrls: ['./upload.component.css']
})
export class UploadComponent implements OnInit {
  results;
  isView=false;
  selectedResult=[];
  selectedFile:File;
  isuplolad=0;
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getActiveBatches();
  }
  getActiveBatches(){
    this.service.get('production/lot/manufacturing.php?type=getActiveBatches').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }
  onFileChanged(event) {
    this.selectedFile = event.target.files[0];
    this.isuplolad = 1;
  }

  uploadLMR(data){
    if(!data.valid){
      alertify.error('Data Save Successfuly');
      return 0;
    }
   
    const temp=data.value;
    const uploadData = new FormData();
    for(let key in temp){
      let value=temp[key];
      uploadData.append(key, value);
    }

    uploadData.append('batch_size',this.selectedResult['batch_size']);
    if (this.selectedFile !== undefined) {
      uploadData.append('lmrfile', this.selectedFile, this.selectedFile.name);
    }
    this.service.post('production/lot/manufacturing.php?type=uploadLMR&id='+this.selectedResult['id'], uploadData).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('data save Successfully');
        data.resetForm();
        this.getActiveBatches();
        this.isView=false;
      } else {
        alertify.error(response['status']);
      }
    });

  }

}
