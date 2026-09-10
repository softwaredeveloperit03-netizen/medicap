import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-verify',
  templateUrl: './verify.component.html',
  styleUrls: ['./verify.component.css']
})
export class VerifyComponent implements OnInit {

  isView = false;
  results;

  selectedReport = [];
  remark = '';
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getActiveChangeControls();
  }

  getActiveChangeControls() {
    this.service.get('changecontrol.php?type=getActiveChangeControls').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  update(status) {
    this.service.get('changecontrol.php?type=verifyChangeControl&status=' + status + '&id=' + this.selectedReport['id'] + '&remark=' + this.remark).subscribe(response => {
      if (response['status']) {
        alert('Change Control Updated Successfully');
        this.isView = false;
        this.getActiveChangeControls();
        this.remark = '';
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
