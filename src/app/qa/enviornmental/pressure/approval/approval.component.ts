import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  isView = false;
  results;

  selectedResult = [];

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingPressures();
  }
  getPendingPressures(){
    this.service.get('qa/pressure.php?type=getPendingPressures').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  update(status) {
    this.service.get('qa/pressure.php?type=updatePressure&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Updated Successfully');
        this.isView = false;
        this.getPendingPressures();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
