import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-sampling-allocation',
  templateUrl: './sampling-allocation.component.html',
  styleUrls: ['./sampling-allocation.component.css']
})
export class SamplingAllocationComponent implements OnInit {
  loading;
  results;
  point_no;
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
    this.point_no = this.selectedPlan['point_no'];
    console.log(this.point_no);
    this.getQcPersons();
    this.isAllocate = true;
  }

  getQcPersons() {
    this.service.get('employee.php?type=getQCPersons').subscribe(response => {
      this.persons = response;
    });
  } 

  allocatePerson() {
    this.service.get('qc/water.php?type=allocateSamplingPerson&sampling_person=' + this.sampling_person + '&schedule_id=' + this.selectedPlan['id']).subscribe(response => {
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
