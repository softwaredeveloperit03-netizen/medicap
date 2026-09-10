import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-implementation',
  templateUrl: './implementation.component.html',
  styleUrls: ['./implementation.component.css']
})
export class ImplementationComponent implements OnInit {
  constructor(private service: DataAccessService) {  }

  ngOnInit(): void {
    this.getPendingSopForFinalUpload();
  }

  result;
  isView = false;
  selectedResult;

  getPendingSopForFinalUpload() {
    this.service.get('sops.php?type=getPendingSopForFinalUpload&deptName='+localStorage.getItem('department')).subscribe((response: any) => {
      this.result = response;
     });
  }

  view(i){
    this.selectedResult = this.result[i];
    this.isView = true;
  }


  viewFile(url) {
    url = this.service.url + '../../upload/Sops/' + url +'?v=1';
   window.open(url, '_blank');
  }



 sopFile: File;
 
 onFileChanged(event) {
   this.sopFile = event.target.files[0];
 }



 reviewSop(data){

    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    let temp = data.value;
 
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    } 
 
    if (this.sopFile !== undefined) {
      uploadData.append('sopFile', this.sopFile, this.sopFile.name);
    } 
 
    uploadData.append('sopNo', this.selectedResult['sopNo']);
    uploadData.append('supersedNo', this.selectedResult['supersedNo']);
    uploadData.append('version_no', this.selectedResult['version_no']);
    uploadData.append('sopName', this.selectedResult['sopName']);

 
  this.service.post('sops.php?type=finalUploadSignedCopy&iniId='+this.selectedResult['iniId']+
    '&sopsId='+this.selectedResult['sopsId'], uploadData).subscribe(
    (response) => {
      if (response['status'] === 'success') {
        alert('Uploaded Successfully !!!!!!');
        this.getPendingSopForFinalUpload();
         this.isView = false;
      } else {
        alert('Failed: An error occurred, please try again!');
      }
    }
  );

 }




}
