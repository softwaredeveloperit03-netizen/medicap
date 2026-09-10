import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {

  results;

  selectedTesting = [];
  isView = false;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getInprocessStabilityTestings();
  }

  getInprocessStabilityTestings() {
    this.service.get('stability.php?type=getInprocessStabilityTestings').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedTesting = this.results[index];
    this.isView = true;
  }

  save() {
    this.service.post('stability.php?type=saveStabilityTesting', JSON.stringify(this.selectedTesting)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Stability Testing Saved Successfully');
        this.getInprocessStabilityTestings();
        this.isView = false;
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
