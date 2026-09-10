import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-sampling-form',
  templateUrl: './sampling-form.component.html',
  styleUrls: ['./sampling-form.component.css']
})
export class SamplingFormComponent implements OnInit {
  results;
  selectedStability = [];
  isView = false;
  constructor(public service: DataAccessService) { }

  ngOnInit(): void {
    this.getStabilities();
  }

  getStabilities() {
    this.service.get('stability.php?type=getInprocessStabilitySampling').subscribe(response => {
      this.results = response;
    });
  }

  viewProtocol(index) {
    this.selectedStability = this.results[index];
    this.isView = true;
  }

  saveSampling(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.service.post('stability.php?type=saveStabilitySampling&stability_no=' + this.selectedStability['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Sampling Completed Successfully');
        this.getStabilities();
        this.isView = false;
      } else {
        alert('An error occured, please try again!');
      }
    });
  }

}
