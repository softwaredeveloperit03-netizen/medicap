import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-implementation',
  templateUrl: './implementation.component.html',
  styleUrls: ['./implementation.component.css']
})
export class ImplementationComponent implements OnInit {

  isView = false;
  results;

  selectedReport = [];
  remark = '';
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getApprovedChangeControls();
  }

  getApprovedChangeControls() {
    this.service.get('/management/changecontrol.php?type=getApprovedChangeControls').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  update(status) {
    let temp = {};
    temp['status'] = status;
    temp['ctrl_no'] = this.selectedReport['ctrl_no'];
    temp['remark'] = this.remark;
    this.service.post('changecontrol.php?type=closeChangeControl', JSON.stringify(temp)).subscribe(response => {
      if (response['status']) {
        alert('Change Control Updated Successfully');
        this.isView = false;
        this.remark = '';
        this.getApprovedChangeControls();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
