import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-grn',
  templateUrl: './grn.component.html',
  styleUrls: ['./grn.component.css']
})
export class GrnComponent implements OnInit {

  isNew = false;
  results;
  materials;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getSamplingRecords();
  }

  getSamplingRecords() {
    this.service.get('qc/sampling.php?type=getSamplingRecords').subscribe(response => {
      this.results = response;
    });
  }

  new() {
    this.getMaterials();
    this.isNew = true;
  }

  getMaterials() {
    this.service.get('qc/sampling.php?type=getMaterials').subscribe(response => {
      this.materials = response;
    });
  }

  saveSamplingRequest(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('qc/sampling.php?type=saveSamplingRequest', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Saved Successfully');
        this.isNew = false;
        this.getSamplingRecords();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
