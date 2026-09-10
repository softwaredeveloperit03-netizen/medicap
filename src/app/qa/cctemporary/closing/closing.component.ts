import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;
@Component({
  selector: 'app-closing',
  templateUrl: './closing.component.html',
  styleUrls: ['./closing.component.css']
})
export class ClosingComponent implements OnInit {

  additional_evaluation='';
  results;
  isView=false;
  selectedResult=[];

  selectedFile:File;

  selectedIndex = -1;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getInitiatedCC();
  }

  getInitiatedCC(){
    this.service.get('qms/cctemporary.php?type=getPendingClosing').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.selectedIndex = index;
    this.isView=true;
  }

  viewfile(link) {
    window.open(this.service.url + 'upload/cctemporary/' + link);
  }

 

  save(data){
    if(!data.valid){
      alertify.error('All feilds are required');
      return;
    }
    let temp=data.value;
    temp['cc_no']=this.selectedResult['cc_no'];
    this.service.post('qms/cctemporary.php?type=closeCC'+'&id='+this.selectedResult['id'],JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data updated Successfully!');
        this.isView = false;
        this.getInitiatedCC();
      }else{
        alertify.error('Failed an error occured,please try again!');
      }
    });
  }

  onFileChanged(event) {
    this.selectedFile = event.target.files[0];
  }

  add(data) {
    if(!data.valid){
      alertify.error("All fields are required");
      return;
    }
    const temp = data.value;
    const uploadData = new FormData();

    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }

    if (this.selectedFile !== undefined) {
      uploadData.append('attachment', this.selectedFile, this.selectedFile.name);
    }

    uploadData.append('type', 'CLOSURE');

    this.service.post('qms/cctemporary.php?type=uploadAttachment&cc_no='+this.selectedResult['cc_no'],uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        this.getInitiatedCC();
        alertify.success('Attachment Uploaded Successfully');
        // this.isView=false;
        // this.getPendingAttachements();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
}
