import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approve2',
  templateUrl: './approve2.component.html',
  styleUrls: ['./approve2.component.css']
})
export class Approve2Component implements OnInit {

  isView = false;
  results;
  review3_comment='';
  selectedFile:File;

  selectedDev = [];
  remark = '';
  comment='';
  extension='';
  capas=[];

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getInprocessCapa();
  }
  getInprocessCapa(){
    this.service.get('qms/capa.php?type=getApproval2').subscribe(response => {
      this.results = response;
    });
  }
  viewCapa(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }
  
  update(value) {
    this.service.get('qms/capa.php?type=updateApproval2&status=' + value+'&capa_no='+this.selectedDev['capa_no']).subscribe(response => {
      if (response['status'] == 'success') {
        this.getInprocessCapa();
        this.isView = false;
        alertify.success('Incident Updated Successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }


}
