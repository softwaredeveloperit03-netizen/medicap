  import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-verification',
  templateUrl: './verification.component.html',
  styleUrls: ['./verification.component.css']
})
export class VerificationComponent implements OnInit {

  isView = false;
  results;
  review3_comment='';
  selectedFile:File;

  selectedDev = [];
  remark = '';
  comment='';
  extension='';
  capas=[];
  capa_status='';

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getCapaDeails();
    
  }

  getCapaDeails(){
    this.service.get('qms/capa.php?type=getCAPAclosingVerification').subscribe(response => {
      this.results = response;
    });
  }
  viewIncident(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }
  save(data) {
    if(!data.valid){
      alertify.error("All fields are required");
      return;
    }
    this.service.post('qms/capa.php?type=capaVerify&capa_no='+this.selectedDev['capa_no'],JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getCapaDeails();
        this.isView = false;
        alertify.success('Record Updated Successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
}
