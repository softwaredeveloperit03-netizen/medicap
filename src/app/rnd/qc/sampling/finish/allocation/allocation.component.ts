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

  selectedSampling = [];
  employees;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getSamplingRecords();
  }

  getSamplingRecords() {
    this.service.get('qc/sampling/finish.php?type=getPendingAllocation').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedSampling = this.results[index];
    this.isView = true;
    this.getQcPersons();
  }

  getQcPersons() {
    this.service.get('qc/sampling/finish.php?type=getQcPersons').subscribe(response => {
      this.employees = response;
    });
  }

  allocatePerson(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['id'] = this.selectedSampling['id'];
    this.service.post('qc/sampling/finish.php?type=allocatePerson', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Sampling Person Allocated Successfully');
        this.isView = false;
        this.getSamplingRecords();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
