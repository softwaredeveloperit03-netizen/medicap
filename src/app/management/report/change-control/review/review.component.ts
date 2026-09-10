import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-review',
  templateUrl: './review.component.html',
  styleUrls: ['./review.component.css']
})
export class ReviewComponent implements OnInit {

  isView = false;
  results;

  selectedReport = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getDepartments();
  }

  getDepartments() {
    this.service.get('management/changecontrol.php?type=getInprocessDept').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  update(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['ctrl_no'] = this.selectedReport['ctrl_no'];
    temp['dept_id'] = this.selectedReport['dept_id'];
    this.service.post('management/changecontrol.php?type=reviewChangeControl', JSON.stringify(temp)).subscribe(response => {
      if (response['status']) {
        alert('Change Control Updated Successfully');
        this.isView = false;
        this.getDepartments();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
