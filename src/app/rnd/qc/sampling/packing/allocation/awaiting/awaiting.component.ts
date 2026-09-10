import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {

  isView = false;
  results;

  selectedSampling = [];
  employees;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingAllocations();
  }

  getPendingAllocations(){
    this.service.get('qc/sampling/packing.php?type=getPendingAllocation').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedSampling = this.results[index];
    this.isView = true;
    this.getQcPersons();
  }

  getQcPersons() {
    this.service.get('qc/sampling.php?type=getQcPersons').subscribe(response => {
      this.employees = response;
    });
  }

  allocatePerson(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    //temp['id'] = this.selectedSampling['id'];
    this.service.post('qc/sampling/packing.php?type=allocatePerson&id=' + this.selectedSampling['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Sampling Person Allocated Successfully');
        this.isView = false;
        this.getPendingAllocations();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
