import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-closing',
  templateUrl: './closing.component.html',
  styleUrls: ['./closing.component.css']
})
export class ClosingComponent implements OnInit {
  isView = false;
  results;
  review3_comment='';
  selectedFile:File;
  attachments; 
  selectedDev = [];
  remark = '';
  comment='';
  extension='';
  capas=[];
  id;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getInprocessIncidents();
  }

  getInprocessIncidents(){
    this.service.get('qms/capa.php?type=getCAPAclosing').subscribe(response => {
      this.results = response;
    });
  }
  viewIncident(i) {
    this.selectedDev = this.results[i]; 
    this.isView = true;
    this.getCapaDetails();
  }
  
  onFileChanged(event){
  this.selectedFile=event.target.files[0];
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

    this.service.post('qms/capa.php?type=uploadAttachment&capa_no='+this.selectedDev['capa_no'],uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        this.getCapaDetails();
        alertify.success('Attachment Uploaded Successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
  viewfile(link) {
    window.open(this.service.url + '../upload/incident/' + link);
  }
  getCapaDetails() {
    this.service.get('qms/capa.php?type=getCapaDetails&capa_no='+this.selectedDev['capa_no']).subscribe((response: any) => {
      this.selectedDev = response;
    });
  }
  delete(id){ 
    this.service.get('qms/capa.php?type=deleteAttachment&id='+id).subscribe((response: any) => {
      if (response['status'] == 'success') {
        this.getCapaDetails();
        alertify.success('Attachment Delete Successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  saveClosing() {
    this.service.get('qms/capa.php?type=saveClosing&capa_no='+this.selectedDev['capa_no']).subscribe(response => {
      if (response['status'] == 'success') {
        this.router.navigate(['/qa/capa/closing']);
        alertify.success('Capa Updated Successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
