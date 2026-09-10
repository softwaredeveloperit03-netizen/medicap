import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-sampling',
  templateUrl: './sampling.component.html',
  styleUrls: ['./sampling.component.css']
})
export class SamplingComponent implements OnInit {

  unit = '';
  isView = false;
  results;
  units;
  lists: any[] = [];
  selectedPlan: any = {};
  selectedTests: any[] = [];
  bottleDraft = {
    bottle_no: '',
    sampling_qty: null,
    microbiology_qty: null
  };
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
    this.resetCollectionState();
    this.selectedTests = Array.isArray(this.selectedPlan?.tests)
      ? this.selectedPlan.tests.map((t: any) => ({ ...t, selected: true }))
      : [];
    this.isView = true;
  }

  resetCollectionState() {
    this.lists = [];
    this.unit = '';
    this.bottleDraft = {
      bottle_no: '',
      sampling_qty: null,
      microbiology_qty: null
    };
  }

  addSampling() {
    const bottleNo = String(this.bottleDraft.bottle_no || '').trim();
    const samplingQty = Number(this.bottleDraft.sampling_qty);
    const microbiologyQty = Number(this.bottleDraft.microbiology_qty);

    if (!bottleNo) {
      alertify.error('Bottle No is required');
      return;
    }
    if (!Number.isFinite(samplingQty) || samplingQty <= 0) {
      alertify.error('Chemical sampling qty must be greater than 0');
      return;
    }
    if (!Number.isFinite(microbiologyQty) || microbiologyQty < 0) {
      alertify.error('Microbiology qty must be 0 or greater');
      return;
    }
    const duplicate = this.lists.some((x: any) => String(x.bottle_no).trim().toLowerCase() === bottleNo.toLowerCase());
    if (duplicate) {
      alertify.error('Bottle No already added');
      return;
    }

    this.lists.push({
      bottle_no: bottleNo,
      sampling_qty: samplingQty,
      microbiology_qty: microbiologyQty
    });

    this.bottleDraft = {
      bottle_no: '',
      sampling_qty: null,
      microbiology_qty: null
    };
  }

  removeSampling(index: number) {
    this.lists.splice(index, 1);
  }

  get chemical_qty(): number {
    return (this.lists || []).reduce((sum: number, item: any) => sum + (Number(item.sampling_qty) || 0), 0);
  }

  get microbiology_qty(): number {
    return (this.lists || []).reduce((sum: number, item: any) => sum + (Number(item.microbiology_qty) || 0), 0);
  }


  saveSampling(form?) {
    if (!this.lists || this.lists.length === 0) {
      alertify.error('Add at least one bottle entry');
      return;
    }
    const finalTests = (this.selectedTests || []).filter((t: any) => t.selected);
    if (finalTests.length === 0) {
      alertify.error('Select at least one test');
      return;
    }
    if (!this.unit) {
      alertify.error('Select unit');
      return;
    }

    const payload: any = {
      lists: this.lists,
      tests: finalTests.map((t: any) => {
        const copy = { ...t };
        delete copy.selected;
        return copy;
      }),
      water_point_id: this.selectedPlan['water_point_id'] || this.selectedPlan['id'],
      schedule_id: this.selectedPlan['schedule_id'],
      chemical_qty: this.chemical_qty,
      microbiology_qty: this.microbiology_qty,
      unit: this.unit
    };

    this.service.post('qc/water.php?type=saveSampling', JSON.stringify(payload)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Sampling Saved Successfully');
        this.getPendingSamplings();
        this.isView = false;
        this.resetCollectionState();
        this.selectedTests = [];
        this.selectedPlan = {};
        if (form?.reset) {
          form.reset();
        }
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
