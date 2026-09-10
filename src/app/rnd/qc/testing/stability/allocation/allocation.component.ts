import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-allocation',
  templateUrl: './allocation.component.html',
  styleUrls: ['./allocation.component.css']
})
export class AllocationComponent implements OnInit {

  isView = false;
  results;

  employees;
  selectedTesting = [];
  person = '';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingStabilityTestings();
  }

  getPendingStabilityTestings() {
    this.service.get('stability.php?type=getPendingStabilityTestings').subscribe(response => {
      this.results = response;
    });
  }
  
  getQCOfficers() {
    this.service.get('stability.php?type=getQCOfficers').subscribe(response => {
      this.employees = response;
    });
  }

  view(index) {
    this.selectedTesting = this.results[index];
    this.getQCOfficers();
    this.isView = true;
  }

  allocateTesting() {
    this.service.get('stability.php?type=allocateTesting&person=' + this.person + '&testing_no=' + this.selectedTesting['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Testing Person Allocated Successfully');
        this.person = '';
        this.isView = false;
        this.getPendingStabilityTestings();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
