import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  results: any[] = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingHPLC();
  }

  getPendingHPLC(){
    this.service.get('qc/hplc.php?type=getPendingHPLC').subscribe(response => {
      this.results = Array.isArray(response) ? response : [];
    });
  }

  update(status, id) {
    this.service.get('qc/hplc.php?type=updateHPLC&status=' + status + '&id=' + id).subscribe(response => {
      if (response['status'] == 'success') {
       alertify.success('Record Updated Successfully');
        this.getPendingHPLC();
      } else {
       alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
