  import { Component, OnInit } from '@angular/core';
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
    constructor(private service:DataAccessService) { }
  
    ngOnInit(): void {
      this.getPendingAttachements();
    }
    getPendingAttachements(){
      this.service.get('qms/ccpermanant.php?type=getPendingAttachements').subscribe(response=>{
        this.results=response;
      });
    }
  
    view(index){
      this.selectedResult=this.results[index];
      this.isView=true;
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
      uploadData.append('type',"PROPOSED CHANGE");
      this.service.post('qms/ccpermanant.php?type=uploadAttachment&cc_no='+this.selectedResult['cc_no'],uploadData).subscribe(response => {
        if (response['status'] == 'success') {
          this.getCCDetails();
          alertify.success('Attachment Uploaded Successfully');
          // this.isView=false;
          // this.getPendingAttachements();
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
    }
  
    viewfile(link) {
      window.open(this.service.url + 'upload/ccpermanant/' + link);
    }
  
  
    getCCDetails() {
      this.service.get('qms/ccpermanant.php?type=getCCDetails&cc_no='+this.selectedResult['cc_no']).subscribe((response: any) => {
        this.selectedResult = response;
      });
    }
    delete(id){ 
      this.service.get('qms/ccpermanant.php?type=deleteAttachment&id='+id).subscribe((response: any) => {
        if (response['status'] == 'success') {
          this.getCCDetails();
          alertify.success('Attachment Delete Successfully');
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
    }
  }
  