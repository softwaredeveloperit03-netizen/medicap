import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-sampling',
  templateUrl: './sampling.component.html',
  styleUrls: ['./sampling.component.css']
})
export class SamplingComponent implements OnInit {

  isView = false;
  results;

  selectedPlan = [];
  sampling_qty = 0;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingSamplings();
  }

  getPendingSamplings() {
    this.service.get('qc/water.php?type=getPendingSamplings').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedPlan = this.results[index];
    this.isView = true;
  }

  saveSampling() {
    this.selectedPlan['sampling_qty'] = this.sampling_qty;
    this.service.post('qc/water.php?type=saveSampling', JSON.stringify(this.selectedPlan)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.error('Sampling Saved Successfully');
        this.getPendingSamplings();
        this.isView = false;
        this.sampling_qty = 0;
      } else {
        alertify.success('Failed: An error occured, please try again!');
      }
    });
  }

}
