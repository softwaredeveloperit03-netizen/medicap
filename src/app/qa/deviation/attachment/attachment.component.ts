import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-attachment',
  templateUrl: './attachment.component.html',
  styleUrls: ['./attachment.component.css']
})
export class AttachmentComponent implements OnInit {
  results;
  selectedResult=[];
  isView=false;
  selectedFile:File;
    selectedDev;
    

    constructor(public service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getPendingAttachments();
  }

  getPendingAttachments(){
    this.service.get('qms/deviation.php?type=getPendingAttachments').subscribe(response=>{
      this.results=response;
      console.log('hiii',this.results);
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  onFileChanged(event) {
    this.selectedFile = event.target.files[0];
  }
  viewfile(url){
    url = this.service.url + '../../upload/deviation/' + url;
    window.open(url, '_blank');
     // window.open(this.selectedResult['documents']);
  }
 
  // save(data) {
  //   if (!data.valid) {
  //     alert('An error occured, please try again!');
  //     return;
  //   }
  //   let temp = data.value;
  //   this.service.get('deviation.php?type=saveReview&id=' + this.selectedDev['comm_no'] + '&comment=' + temp['comment'] + '&dev_no='+ this.selectedDev['dev_no']).subscribe(response => {
  //     if (response['status']) {
  //       alert("Review Submitted Successfully");
  //       this.isView = false;
  //      } else {
  //       alert('Failed: An error occured, please try again!');
  //     }
  //   });
  // }

  
  save(data){
    // if(data.valid)
    this.service.post('deviation.php?type=saveReview',JSON.stringify(data)).subscribe(response=>{
      if (response['status']) {
        alertify.success("Review Submitted Successfully");
      //  data.reset();
      this.router.navigate(['/qa/deviation']);
      }
  
    else{
      alertify.error('Failed: An error occured, please try again!');
    }
  });
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
      uploadData.append('attachment1', this.selectedFile, this.selectedFile.name);
    }

    this.service.post('qms/deviation.php?type=uploadAttachment&deviation_no='+this.selectedResult['deviation_no'],uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        this.getDeviationDetails();
        alertify.success('Attachment Uploaded Successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  getDeviationDetails() {
    this.service.get('qms/deviation.php?type=getDeviationDetails&deviation_no='+this.selectedResult['deviation_no']).subscribe((response: any) => {
      this.selectedResult = response;
    });
  }

}
