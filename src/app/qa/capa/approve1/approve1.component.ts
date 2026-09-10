import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approve1',
  templateUrl: './approve1.component.html',
  styleUrls: ['./approve1.component.css']
})
export class Approve1Component implements OnInit {

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
    this.service.get('qms/capa.php?type=getApproval1').subscribe(response => {
      this.results = response;
    });
  }
  viewCapa(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }
  
  update(value) {
    this.service.get('qms/capa.php?type=updateApproval1&status=' + value+'&capa_no='+this.selectedDev['capa_no']).subscribe(response => {
      if (response['status'] == 'success') {
        this.getInprocessCapa();
        this.isView = false;
        alertify.success('Capa Updated Successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }


}
