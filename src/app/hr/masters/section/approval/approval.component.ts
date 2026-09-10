import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

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
        alert('Record Updated Successfully');
        this.getPendingSections();
      } else {
         alert('Failed: An error occured, please try again!');
      }
    });
  }

}
