import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-sampling',
  templateUrl: './sampling.component.html',
  styleUrls: ['./sampling.component.css']
})
export class SamplingComponent implements OnInit {

  unit='';
  isView = false;
  results;
  units
  selectedPlan = [];
  chemical_qty = 0;
  microbiology_qty =0;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingSamplings();
    this.getUnits();
  }
  getUnits(){
    this.service.get('common.php?type=getUnits').subscribe(response=>{
      this.units = response
    });
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
    this.selectedPlan['chemical_qty'] = this.chemical_qty;
    this.selectedPlan['microbiology_qty'] = this.microbiology_qty;
    this.selectedPlan['unit'] = this.unit;
    this.service.post('qc/water.php?type=saveSampling', JSON.stringify(this.selectedPlan)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Sampling Saved Successfully');
        this.getPendingSamplings();
        this.isView = false;
        this.chemical_qty = 0;
        this.microbiology_qty = 0;
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
