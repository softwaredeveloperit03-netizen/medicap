import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-standardisation',
  templateUrl: './standardisation.component.html',
  styleUrls: ['./standardisation.component.css']
})
export class StandardisationComponent implements OnInit {

  isView = false;
  results;
  selectedSolution = [];

  standardization = [
    {
      "sample_wt": 0,
      "volume": 0,
      "factor": 0,
      "strength": ''
    },
    {
      "sample_wt": 0,
      "volume": 0,
      "factor": 0,
      "strength": ''
    },
    {
      "sample_wt": 0,
      "volume": 0,
      "factor": 0,
      "strength": ''
    }
  ]
  mean = 0;
  sd = 0;
  rsd = 0;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingStandardization();
  }

  getPendingStandardization() {
    this.service.get('qc/volumetric.php?type=getPendingStandardization').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedSolution = this.results[index];
    this.isView = true;
  }

  calculateStrength() {
    let mean = 0;
    for (let i = 0; i < this.standardization.length; i++) {
      let data = this.standardization[i];
      let strength = (+data['sample_wt'] * +this.selectedSolution['strength']) / (data['volume'] * data['factor']);
      data['strength'] = strength.toFixed(2);

      this.standardization[i] = data;

      mean += +strength;
    }
    this.mean = +(mean / 3).toFixed(2);
  }

  saveStandardization() {
    if(this.selectedSolution['mean'] !=0){
      alertify.error('All feilds are required');
      return;
    }
    this.selectedSolution['mean'] = this.mean;
    this.selectedSolution['sd'] = this.sd;
    this.selectedSolution['rsd'] = this.rsd;
    this.selectedSolution['details'] = this.standardization;

    this.service.post('qc/volumetric.php?type=saveStandardization', JSON.stringify(this.selectedSolution)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.isView = false;
        this.getPendingStandardization();
      } else {
        alertify.error('Failed: An error occured, Please try again!');
      }
    });
  }

}
