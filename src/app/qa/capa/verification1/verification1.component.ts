import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-verification1',
  templateUrl: './verification1.component.html',
  styleUrls: ['./verification1.component.css']
})
export class Verification1Component implements OnInit {

  isView = false;
  results;
  review3_comment='';
  selectedFile:File;

  selectedDev = [];

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getCapaDeails();
  }

  getCapaDeails(){
    this.service.get('qms/capa.php?type=getCAPAclosingVerificationApproval').subscribe(response => {
      this.results = response;
    });
  }
  viewIncident(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }

  save() {
    this.service.get('qms/capa.php?type=capaVerifyApproval&capa_no='+this.selectedDev['capa_no']).subscribe(response => {
      if (response['status'] == 'success') {
        this.getCapaDeails();
        this.isView = false;
        alertify.success('CAPA closed Successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
