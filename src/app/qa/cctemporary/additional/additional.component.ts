import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-additional',
  templateUrl: './additional.component.html',
  styleUrls: ['./additional.component.css']
})
export class AdditionalComponent implements OnInit {
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
    this.service.get('qms/cctemporary.php?type=getPendingAdditionalEvaluation').subscribe(response=>{
      this.results=response;
      if (this.selectedIndex !== -1) {
        this.view(this.selectedIndex);
      }
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.selectedIndex = index;
    this.isView=true;
  }

 
  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }

    let temp= data.value;
    temp['cc_no']=this.selectedResult['cc_no'];
    this.service.post('qms/cctemporary.php?type=saveAdditional'+'&id='+this.selectedResult['id'],JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data updated Successfully!');
        this.selectedIndex = -1;
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

    uploadData.append('type', 'Additional Evaluation Plan');

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

  viewfile(link) {
    window.open(this.service.url + 'upload/cctemporary/' + link);
  }
  
}
