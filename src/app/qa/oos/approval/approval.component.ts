import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isView = false;
  isNew = false;
  selectedEntry;

  Oos = [];

  list;

  constructor(private service: DataAccessService, private router: Router) {

   }

  ngOnInit() {
    this.getApprovedOosData();
  }


  view(index) {
    this.selectedEntry = this.list[index];
    this.isView = true;
  }

  getApprovedOosData() {
    this.service.get('qc/oos.php?type=getCheckedOosData').subscribe(response => {
      this.list = JSON.parse(JSON.stringify(response));
    });
  }

    addVendorAgenda() {
      this.isNew = true;
    }

  close() {
    this.router.navigate(['/oos']);
  }

  updateOos(status) {
    this.service.get('qc/oos.php?type=approveOOS&status=' + status + '&id=' + this.selectedEntry['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('OOS Updated Successfully');
        this.isNew = false;
        this.getApprovedOosData();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
