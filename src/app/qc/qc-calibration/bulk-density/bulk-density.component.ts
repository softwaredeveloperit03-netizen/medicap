import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-bulk-density',
  templateUrl: './bulk-density.component.html',
  styleUrls: ['./bulk-density.component.css']
})
export class BulkDensityComponent implements OnInit {
  isNew = false;
  results;
  bulks;
  selectedResults = []; 
  isView = false;
  bulkmethod = '';

  constructor(private service: DataAccessService,private router:Router) { }


  ngOnInit(): void {
    this.getUSP1BulkDensity();
    this.getUSP2BulkDensity();
  }


  view(index){
    this.selectedResults = this.results[index];
    this.isView = true;
  }

  new() {

    this.isNew = true;
  }

  getUSP1BulkDensity(){
    this.service.get('qc/calibration/bulkdensity.php?type=getUSP1BulkDensity').subscribe(response => {
      this.results = response;
      
    });
  }

  getUSP2BulkDensity(){
    this.service.get('qc/calibration/bulkdensity.php?type=getUSP2BulkDensity').subscribe(response => {
      this.bulks = response;
      
    });
  }

  download() {
    this.service.open('qc/calibration/bulkdensity.php?type=BulkDensityCalibration');
  }

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('qc/calibration/bulkdensity.php?type=saveBulkDensity', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        data.resetForm();
        this.isView = false;
      } else {
        alertify.error('Failed: An error occured, Please try again!');
      }
    });
  }

}
