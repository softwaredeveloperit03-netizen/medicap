import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  results;
  isView;

  selectedHplc = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingHPLC();
  }

  getPendingHPLC(){
    this.service.get('qc/hplc.php?type=getPendingHPLC').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedHplc = this.results[index];
    this.isView = true;
  }

  update(status) {
    this.service.get('qc/hplc.php?type=updateHPLC&status=' + status + '&id=' + this.selectedHplc['id']).subscribe(response => {
      if (response['status'] == 'success') {
       alertify.success('Record Updated Successfully');
        this.isView = false;
        this.getPendingHPLC();
      } else {
       alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
