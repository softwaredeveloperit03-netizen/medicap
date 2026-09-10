import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-charging',
  templateUrl: './charging.component.html',
  styleUrls: ['./charging.component.css']
})
export class ChargingComponent implements OnInit {
  results;
  selectedStability = [];
  isView = false;

  equipments;
  constructor(public service: DataAccessService) { }

  ngOnInit(): void {
    this.getStabilities();
    this.getStabilityChembers();
  }

  getStabilities() {
    this.service.get('stability.php?type=getPendingStabilityCharging').subscribe(response => {
      this.results = response;
    });
  }

  getStabilityChembers() {
    this.service.get('stability.php?type=getStabilityChembers').subscribe(response => {
      this.equipments = response;
    });
  }

  viewProtocol(index) {
    this.selectedStability = this.results[index];
    this.isView = true;
  }

  saveSampling(data) {
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

  updateStabilityChember(value, index, action) {
    let batches = this.selectedStability['batches'];
    let batch = batches[index];
    batch[action] = value;
    batches[index] = batch;
    this.selectedStability['batches'] = batches;
  }

  saveStabilityCharging(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service.post('stability.php?type=saveStabilityCharging&stability_no=' + this.selectedStability['id'], JSON.stringify(this.selectedStability['batches'])).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Stability Sample Charging Successfully');
        this.isView = false;
        this.getStabilities();
      } else {
        alert('An error occured, please try again!');
      }
    });
  }

  saveCharging() {
    this.service.post('stability.php?type=saveStabilityCharging&stability_no=' + this.selectedStability['id'], JSON.stringify(this.selectedStability['conditions'])).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Stability Sample Charging Successfully');
        this.isView = false;
        this.getStabilities();
      } else {
        alert('An error occured, please try again!');
      }
    });
  }

}
