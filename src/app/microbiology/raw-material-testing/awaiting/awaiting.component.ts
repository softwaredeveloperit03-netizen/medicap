import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;



@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {

  isView = false;
  results;
  isProceed = false;
  selectedResult = [];
  material_remark = '';

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingTestingForms();
  }

  getPendingTestingForms() {
    this.service.get('qc/testing/raw.php?type=getPendingTestingForms').subscribe(response => {
      this.results = response;
    });
  }

  viewSpecification(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  
  proceed(index){
    let bat = this.selectedResult['tests'];

  //  this.selectedMaterial = this.materials[index];
   
    this.isProceed = true;
  }

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp  = this.selectedResult;
    temp['batch_no'] = this.selectedResult['batch_no'];
    temp['grn_no'] = this.selectedResult['grn_no'];
    temp['grn_date'] = this.selectedResult['grn_date'];
    temp['material_remark'] = this.selectedResult['material_remark'];
    this.service.post('qc/testing/raw.php?type=saveTestingForm', JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(response['msg']);
        this.isView = false;
        this.getPendingTestingForms();
      } else {
        alertify.error(response['msg']);
      }
    });
  }

}
