import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-change-control-approval',
  templateUrl: './approval.component.html'
})
export class ChangeControlApprovalComponent implements OnInit {
  
  isView = false;
  results;

  selectedReport = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getCheckedChangeControls();
  }

  getCheckedChangeControls() {
    this.service.get('changecontrol.php?type=getCheckedChangeControls').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  update(status, data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['status'] = status;
    temp['ctrl_no'] = this.selectedReport['ctrl_no'];
    temp['department'] = this.selectedReport['department'];
    temp['change_related'] = this.selectedReport['change_related'];
    this.service.post('changecontrol.php?type=approveQAChange', JSON.stringify(temp)).subscribe(response => {
      if (response['status']) {
        alert('Change Control Updated Successfully');
        this.isView = false;
        this.getCheckedChangeControls();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
