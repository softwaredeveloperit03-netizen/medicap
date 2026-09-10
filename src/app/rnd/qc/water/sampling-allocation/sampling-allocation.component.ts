import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-sampling-allocation',
  templateUrl: './sampling-allocation.component.html',
  styleUrls: ['./sampling-allocation.component.css']
})
export class SamplingAllocationComponent implements OnInit {

  results;

  selectedPlan = [];
  isAllocate = false;
  persons;

  sampling_person = '';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingSamplingAllocations();
  }

  getPendingSamplingAllocations() {
    this.service.get('qc/water.php?type=getPendingSamplingAllocations').subscribe(response => {
      this.results = response;
    });
  }

  allocate(index) {
    this.selectedPlan = this.results[index];
    this.getQcPersons();
    this.isAllocate = true;
  }

  getQcPersons() {
    this.service.get('qc/water.php?type=getQcPersons').subscribe(response => {
      this.persons = response;
    });
  }

  allocatePerson() {
    this.service.get('qc/water.php?type=allocateSamplingPerson&person=' + this.sampling_person + '&id=' + this.selectedPlan['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Sampling Person Allocated Successfully');
        this.sampling_person = '';
        this.isAllocate = false;
        this.getPendingSamplingAllocations();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
