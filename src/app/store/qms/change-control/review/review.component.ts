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
  ctrl_no='';

  selectedReport = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getInprocessDept();
  }

  getInprocessDept() {
    this.service.get('store/changecontrol.php?type=getInprocessDept').subscribe(response => {
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
   // temp['ctrl_no'] = this.selectedReport['ctrl_no'];
    temp['dept_id'] = this.selectedReport['dept_id'];
    this.service.post('changecontrol.php?type=reviewChangeControl&ctrl_no=' + this.selectedReport['ctrl_no'], JSON.stringify(temp)).subscribe(response => {
      if (response['status']) {
        alert('Change Control Updated Successfully');
        this.isView = false;
        this.getInprocessDept();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
