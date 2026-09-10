import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-closing1',
  templateUrl: './closing1.component.html',
  styleUrls: ['./closing1.component.css']
})
export class Closing1Component implements OnInit {

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
    this.service.get('qms/capa.php?type=getCAPAclosingApproval').subscribe(response => {
      this.results = response;
    });
  }
  viewIncident(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }
  save() {
    this.service.get('qms/capa.php?type=capaClosingApproval&capa_no='+this.selectedDev['capa_no']).subscribe(response => {
      if (response['status'] == 'success') {
        this.getCapaDeails();
        this.isView = false;
        alertify.success('Incident Updated Successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
