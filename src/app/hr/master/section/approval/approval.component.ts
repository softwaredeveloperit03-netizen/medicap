import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  results;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingSections();
  }

  getPendingSections() {
    this.service.get('hr/section.php?type=getPendingSections').subscribe(response => {
      this.results = response;
    });
  }

  update(status, id) {
    this.service.get('hr/section.php?type=updateSection&status= ' + status + '&id=' + id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Updated Successfully');
        this.getPendingSections();
      } else {
         alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
