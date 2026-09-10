import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

  isView = false;
  results;

  selectedReport = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingChangeControls();
  }

  getPendingChangeControls() {
    this.service.get('changecontrol.php?type=getPendingChangeControls').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  update(status) {
    this.service.get('changecontrol.php?type=checkChangeControl&status=' + status + '&id=' + this.selectedReport['id']).subscribe(response => {
      if (response['status']) {
        alertify.success('Change Control Updated Successfully');
        this.isView = false;
        this.getPendingChangeControls();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
