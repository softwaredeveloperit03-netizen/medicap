import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-sampling-allocation',
  templateUrl: './sampling-allocation.component.html',
  styleUrls: ['./sampling-allocation.component.css']
})
export class SamplingAllocationComponent implements OnInit {
  
  results;
  selectedStability = [];
  isView = false;

  sampling_person = '';
  constructor(public service: DataAccessService) { }

  ngOnInit(): void {
    this.getStabilities();
    this.getQAOfficers();
  }

  getStabilities() {
    this.service.get('stability.php?type=getPendingStabilitySampling').subscribe(response => {
      this.results = response;
    });
  }

  persons;
  getQAOfficers() {
    this.service.get('stability.php?type=getQAOfficers').subscribe(response => {
      this.persons = response;
    });
  }

  viewProtocol(index) {
    this.selectedStability = this.results[index];
    this.isView = true;
  }

  alternate_person;

  saveSampling(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service.get('stability.php?type=allocateSamplingPerson&stability_no=' + this.selectedStability['id'] + '&sampling_person=' + this.sampling_person + '&Altsampling_person=' + this.alternate_person).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Sampling Person Allocated Successfully');
        this.sampling_person = '';
        this.alternate_person  = '';
        this.getStabilities();
        this.isView = false;
      } else {
        alert('An error occured, please try again!');
      }
    });
  }

}
