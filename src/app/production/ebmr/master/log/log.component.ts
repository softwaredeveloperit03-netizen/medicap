import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  dosages;
  dosage_form = '';
  isView = false;
  isNew = false;
  results;

  selectedResult = [];
  specification = [];
  specification1 = [];

  batch_size = 0;
  lots = 0;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getDosages();
    this.getMFRs();
  }

  getDosages() {
    this.service.get('common.php?type=getDosages').subscribe(response => {
      this.dosages = response;
    });
  }

  getMFRs() {
    this.service.get('bmr/mfr.php?type=getMFRs&dosage_form=' + this.dosage_form).subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.specification = this.selectedResult['specification'];
    this.specification1 = this.selectedResult['specification1'];
    this.isView = true;
  }

  new(index) {
    this.selectedResult = this.results[index];
    this.isNew = true;
  }

  close() {
    this.isView = false;
  }

  download() {
    this.service.open('bmr/mfr.php?type=downloadMFRRecord&id=' + this.selectedResult['id']);
    this.isView = false;
  }

  download1(id) {
    this.service.open('bmr/standard.php?type=downloadMFRRecord&id=' + id);
  }

  saveBatchSize() {
    this.selectedResult['new_batch_size'] = this.batch_size;
    this.selectedResult['lots'] = this.lots;
    this.service.post('bmr/mfr.php?type=saveBatchSize', JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('New Batch Size saved successfully!');
        this.isNew = false;
        this.getMFRs();
      } else {
        alert('Failed');
      }
    });
  }

  calculate() {
    let raw_materials = this.selectedResult['raw_materials'];
    for (let i = 0; i < raw_materials.length; i++) {
      let material = raw_materials[i];
      if (material['unit'] == "mg") {
        material["batch_qty"] = (material["qty"] * (this.batch_size / 1000000)).toFixed(2);
        material['batch_unit'] = "Kg";
      } else {
        material["batch_qty"] = (material["qty"] * this.batch_size).toFixed(2);
        material['batch_unit'] = material['unit'];
      }

      let batch_qty = +material["batch_qty"];
      material["lot_qty"] = (+batch_qty / this.lots).toFixed(2);
      material['lot_unit'] = material['batch_unit'];

      raw_materials[i] = material;
    }
    this.selectedResult['raw_materials'] = raw_materials;

    let additional_materials = this.selectedResult['additional_materials'];
    for (let i = 0; i < additional_materials.length; i++) {
      let material = additional_materials[i];
      if (material['unit'] == "mg") {
        material["batch_qty"] = (material["qty"] * (this.batch_size / 1000000)).toFixed(2);
        material['batch_unit'] = "Kg";
      } else {
        material["batch_qty"] = (material["qty"] * this.batch_size).toFixed(2);
        material['batch_unit'] = material['unit'];
      }

      let batch_qty = +material["batch_qty"];
      material["lot_qty"] = (+batch_qty / this.lots).toFixed(2);
      material['lot_unit'] = material['batch_unit'];

      additional_materials[i] = material;
    }
    this.selectedResult['additional_materials'] = additional_materials;

    let packing_materials = this.selectedResult['packing_materials'];
    for (let i = 0; i < packing_materials.length; i++) {
      let material = packing_materials[i];
      if (material['unit'] == "mg") {
        material["batch_qty"] = (material["qty"] * (this.batch_size / 1000000)).toFixed(2);
        material['batch_unit'] = "Kg";
      } else {
        material["batch_qty"] = (material["qty"] * this.batch_size).toFixed(2);
        material['batch_unit'] = material['unit'];
      }

      let batch_qty = +material["batch_qty"];
      material["lot_qty"] = (+batch_qty / this.lots).toFixed(2);
      material['lot_unit'] = material['batch_unit'];

      packing_materials[i] = material;
    }
    this.selectedResult['packing_materials'] = packing_materials;
  }

}
